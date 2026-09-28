<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Mission 11 â€” checkout fiable basÃ© sur MySQL.
 *
 * Le serveur recalcule tout : prix, stock, total. Aucun champ envoyÃ© par
 * le navigateur ne peut fausser la commande. Non destructif :
 * DatabaseTransactions uniquement (pas de migrate:fresh).
 */
class CheckoutValidationTest extends TestCase
{
    use DatabaseTransactions;

    private ?User $producer = null;

    private function producer(): User
    {
        return $this->producer ??= User::factory()->create([
            'role' => 'producer',
            'email' => 'mission11-producer@example.test',
        ]);
    }

    private function client(): User
    {
        return User::factory()->create([
            'role' => 'client',
            'email' => 'mission11-client-' . uniqid() . '@example.test',
        ]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        static $counter = 0;
        $counter++;

        $category = Category::query()->first() ?? Category::create([
            'name' => 'Mission11 Cat',
            'slug' => 'mission11-cat',
        ]);
        $location = Location::query()->first() ?? Location::create([
            'country' => 'Cameroun',
            'region' => 'Centre',
            'city' => 'Bafia',
        ]);

        return Product::create(array_merge([
            'producer_id' => $this->producer()->id,
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => "Mission11 Produit {$counter}",
            'slug' => "mission11-produit-{$counter}-" . uniqid(),
            'description' => 'Produit de test Mission 11.',
            'price' => 1000,
            'unit' => 'kg',
            'stock_quantity' => 10,
            'minimum_order' => 1,
            'is_available' => true,
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));
    }

    private function fillCart(User $client, Product $product, int $quantity): void
    {
        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug, ['quantite' => $quantity]);
    }

    private function checkout(User $client, array $overrides = [])
    {
        return $this->actingAs($client)->post('/checkout', array_merge([
            'nom' => 'Client Test',
            'telephone' => '655000112',
            'adresse' => 'Bafia, Cameroun',
            'moyen_paiement' => 'MTN Mobile Money',
        ], $overrides));
    }

    private function subtotal(User $client): float
    {
        $cart = Cart::where('user_id', $client->id)->firstOrFail();
        $client = $this->client();
        $product = $this->makeProduct(['price' => 1250]);
        $this->fillCart($client, $product, 4);

        $this->checkout($client)->assertRedirect();

        $order = Order::where('client_id', $client->id)->firstOrFail();
        $this->assertSame(5000.0, (float) $order->subtotal); // 1250 * 4, recalculÃ© cÃ´tÃ© serveur
        $this->assertSame(6000.0, (float) $order->total); // 5000 + 1000 frais
        $this->assertSame(1, OrderItem::where('order_id', $order->id)->count());
    }

    // 2. Panier vide -------------------------------------------------------------
    public function test_2_empty_cart_is_rejected(): void
    {
        $client = $this->client();

        $this->checkout($client)->assertSessionHasErrors();

        $this->assertSame(0, Order::where('client_id', $client->id)->count());
    }

    // 3. Prix modifiÃ© -------------------------------------------------------------
    public function test_3_client_modified_price_is_ignored_mysql_price_wins(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['price' => 2000]);
        $this->fillCart($client, $product, 2);

        // The browser lies about the price: the server must recalculate.
        $this->checkout($client, ['total' => 1])
            ->assertRedirect(); // no validation error: 'total' is simply not trusted

        $order = Order::where('client_id', $client->id)->firstOrFail();
        $this->assertSame(4000.0, (float) $order->subtotal); // 2000 * 2 from MySQL
        $this->assertSame(5000.0, (float) $order->total);
        $this->assertNotSame(1.0, (float) $order->total);
    }

    // 4. Produit supprimÃ© ----------------------------------------------------------
    public function test_4_deleted_or_unavailable_product_blocks_the_checkout(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();
        $this->fillCart($client, $product, 2);

        // Product is removed from the catalog after being added to the cart.
        DB::table('products')->where('id', $product->id)->delete();

        $this->checkout($client)->assertSessionHasErrors('panier');
        $this->assertSame(0, Order::where('client_id', $client->id)->count());
    }

    // 5. Stock insuffisant ------------------------------------------------------------
    public function test_5_insufficient_stock_at_checkout_time_rejects_the_order(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();
        $this->fillCart($client, $product, 5);

        // Stock drops after the cart was filled (someone else bought).
        $product->update(['stock_quantity' => 4]);

        $this->checkout($client)->assertSessionHasErrors('panier');
        $this->assertSame(0, Order::where('client_id', $client->id)->count());
        $this->assertSame(4, (int) $product->refresh()->stock_quantity);
    }

    // 6. Utilisateur non autorisÃ© -------------------------------------------------------
    public function test_6_unauthorized_users_cannot_checkout(): void
    {
        $guest = $this->client();
        $product = $this->makeProduct();

        // a) Anonymous.
        $this->post('/checkout', [
            'nom' => 'X',
            'telephone' => '655',
            'adresse' => 'Y',
            'moyen_paiement' => 'MTN',
        ])->assertUnauthorized(); // anonymous POST without JSON header => 401

        // b) Producer (not a client) with items in his cart.
        $producer = $this->producer();
        $this->actingAs($producer)->post('/panier/ajouter/' . $product->slug, ['quantite' => 1]);
        $this->actingAs($producer)->post('/checkout', [
            'nom' => 'P',
            'telephone' => '655',
            'adresse' => 'Z',
            'moyen_paiement' => 'MTN',
        ])->assertForbidden();

        // c) Guest cart attached to nobody â€” cannot be checked out by another client.
        $this->assertSame(0, Order::where('client_id', $producer->id)->count());
        $this->assertSame(0, Order::where('client_id', $guest->id)->count());
    }

    public function test_6b_a_client_cannot_checkout_a_cart_that_is_not_his(): void
    {
        $owner = $this->client();
        $intruder = $this->client();
        $product = $this->makeProduct();
        $this->fillCart($owner, $product, 2);

        // The intruder posts to /checkout but his own cart is empty: the order
        // must be rejected and nothing of the owner's cart may be consumed.
        $this->checkout($intruder)->assertSessionHasErrors('panier');

        $cartId = Cart::where('user_id', $owner->id)->value('id');
        $this->assertSame(2, (int) DB::table('cart_items')->where('cart_id', $cartId)->sum('quantity'));
        $this->assertSame(0, Order::where('client_id', $intruder->id)->count());
    }

    // 7. Total incorrect envoyÃ© par le client ---------------------------------------------
    public function test_7_forged_totals_and_quantities_are_recomputed_by_the_server(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['price' => 1000]);
        $this->fillCart($client, $product, 3);

        // Everything the client could forge is ignored.
        $this->checkout($client, [
            'total' => 0,
            'subtotal' => 0,
            'quantite' => 99999,
            'prix' => 1,
        ])->assertRedirect();

        $order = Order::where('client_id', $client->id)->firstOrFail();
        $this->assertSame(3000.0, (float) $order->subtotal);
        $this->assertSame(4000.0, (float) $order->total);
        $this->assertSame(3, (int) OrderItem::where('order_id', $order->id)->sum('quantity'));
        $this->assertSame(7, (int) $product->refresh()->stock_quantity); // only 3 deducted
    }

    // 8. Double soumission -----------------------------------------------------------------
    public function test_8_double_submission_cannot_create_two_orders(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();
        $this->fillCart($client, $product, 2);

        $this->checkout($client)->assertRedirect();
        $this->checkout($client)->assertSessionHasErrors('panier'); // cart is empty now

        $this->assertSame(1, Order::where('client_id', $client->id)->count());
        $this->assertSame(8, (int) $product->refresh()->stock_quantity); // deducted once only
    }
}
