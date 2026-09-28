<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Mission 10 — transactional stock.
 *
 * The MySQL stock becomes the reliable source of truth when an order is
 * placed: atomic guarded decrement, no oversell, no negative stock, no
 * double deduction. Non destructive: DatabaseTransactions only.
 */
class StockTransactionTest extends TestCase
{
    use DatabaseTransactions;

    private ?User $producer = null;

    private function producer(): User
    {
        return $this->producer ??= User::factory()->create([
            'role' => 'producer',
            'email' => 'mission10-producer@example.test',
        ]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        static $counter = 0;
        $counter++;

        $category = Category::query()->first() ?? Category::create([
            'name' => 'Mission10 Cat',
            'slug' => 'mission10-cat',
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
            'name' => "Mission10 Produit {$counter}",
            'slug' => "mission10-produit-{$counter}-" . uniqid(),
            'description' => 'Produit de test Mission 10.',
            'price' => 1500,
            'unit' => 'kg',
            'stock_quantity' => 10,
            'minimum_order' => 1,
            'is_available' => true,
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));
    }

    private function client(): User
    {
        return User::factory()->create([
            'role' => 'client',
            'email' => 'mission10-client-' . uniqid() . '@example.test',
        ]);
    }

    private function fillCart(User $client, Product $product, int $quantity)
    {
        return $this->actingAs($client)->post('/panier/ajouter/' . $product->slug, ['quantite' => $quantity]);
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

    private function freshStock(Product $product): int
    {
        return (int) Product::query()->where('id', $product->id)->value('stock_quantity');
    }

    // 1. Stock suffisant -------------------------------------------------------
    public function test_1_sufficient_stock_is_reserved_on_order(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 10]);
        $this->fillCart($client, $product, 3);

        $this->checkout($client)->assertRedirect();

        $this->assertSame(7, $this->freshStock($product)); // 10 - 3
        $this->assertSame(1, Order::where('client_id', $client->id)->count());
    }

    // 2. Stock insuffisant ------------------------------------------------------
    public function test_2_insufficient_stock_rejects_the_order_and_deducts_nothing(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 10]);
        $this->fillCart($client, $product, 4);

        // The stock drops AFTER the cart was filled (e.g. other clients bought).
        $product->update(['stock_quantity' => 2]);

        $this->checkout($client)->assertSessionHasErrors('panier');

        $this->assertSame(0, Order::where('client_id', $client->id)->count());
        $this->assertSame(2, $this->freshStock($product)); // untouched
    }

    // 3. Stock zéro -------------------------------------------------------------
    public function test_3_zero_stock_rejects_the_order(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 5]);
        $this->fillCart($client, $product, 1);

        $product->update(['stock_quantity' => 0]);

        $this->checkout($client)->assertSessionHasErrors('panier');

        $this->assertSame(0, Order::where('client_id', $client->id)->count());
        $this->assertSame(0, $this->freshStock($product)); // stays at zero, never below
    }

    // 4. Quantité exacte ---------------------------------------------------------
    public function test_4_ordering_the_exact_stock_reaches_zero_without_going_negative(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 5]);
        $this->fillCart($client, $product, 5);

        $this->checkout($client)->assertRedirect();

        $this->assertSame(0, $this->freshStock($product));
        $this->assertSame(1, Order::where('client_id', $client->id)->count());
    }

    // 5. Tentative de dépassement -------------------------------------------------
    public function test_5_overselling_is_impossible_at_cart_level_and_at_checkout_level(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 5]);

        // a) Cart level: cannot add more than the stock.
        $this->fillCart($client, $product, 6)->assertSessionHasErrors('quantite');
        $this->assertSame(5, $this->freshStock($product));

        // b) Checkout level: the cart holds 4, but the stock is now 3.
        $this->fillCart($client, $product, 4);
        $product->update(['stock_quantity' => 3]);

        $this->checkout($client)->assertSessionHasErrors('panier');

        $this->assertSame(3, $this->freshStock($product));
        $this->assertSame(0, Order::where('client_id', $client->id)->count());
    }

    // 6. Concurrence --------------------------------------------------------------
    public function test_6_two_concurrent_checkouts_cannot_both_take_the_last_unit(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 1]);
        $first = $this->client();
        $second = $this->client();

        // Both clients put the last unit in their cart (cart-level check passes
        // for each of them, that is exactly the race to defeat).
        $this->fillCart($first, $product, 1);
        $this->fillCart($second, $product, 1);

        // First checkout wins the unit.
        $this->checkout($first)->assertRedirect();
        $this->assertSame(0, $this->freshStock($product));

        // Second checkout is atomically rejected: the guarded decrement finds
        // no stock left, the whole transaction (order + items) rolls back.
        $this->checkout($second)->assertSessionHasErrors('panier');

        $this->assertSame(0, $this->freshStock($product));
        $this->assertSame(1, Order::count());
        $this->assertSame($first->id, Order::query()->value('client_id'));
    }

    public function test_6b_multi_line_cart_rolls_back_completely_on_any_stock_shortage(): void
    {
        $client = $this->client();
        $available = $this->makeProduct(['stock_quantity' => 10]);
        $short = $this->makeProduct(['stock_quantity' => 1]);

        $this->fillCart($client, $available, 2);
        $this->fillCart($client, $short, 1);

        // The second line's stock drops after the cart was filled.
        $short->update(['stock_quantity' => 0]);

        $this->checkout($client)->assertSessionHasErrors('panier');

        // The whole transaction rolled back: no order, no partial reservation.
        $this->assertSame(0, Order::where('client_id', $client->id)->count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(10, $this->freshStock($available));
        $this->assertSame(0, $this->freshStock($short));
    }

    // 7. Jamais de stock négatif ----------------------------------------------------
    public function test_7_stock_never_goes_negative_no_matter_the_attempt(): void
    {
        $product = $this->makeProduct(['stock_quantity' => 2]);

        $clients = [$this->client(), $this->client(), $this->client()];
        foreach ($clients as $client) {
            $this->fillCart($client, $product, 2);
        }

        $this->checkout($clients[0])->assertRedirect(); // 2 - 2 = 0
        $this->checkout($clients[1])->assertSessionHasErrors('panier');
        $this->checkout($clients[2])->assertSessionHasErrors('panier');

        $this->assertSame(0, $this->freshStock($product));
        $this->assertTrue($this->freshStock($product) >= 0);
        $this->assertSame(1, Order::count());
    }

    // 8. Non double déduction ---------------------------------------------------------
    public function test_8_the_stock_is_deducted_exactly_once_per_order(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 10]);
        $this->fillCart($client, $product, 3);

        $this->checkout($client)->assertRedirect();

        $this->assertSame(7, $this->freshStock($product));

        // Retrying the checkout cannot deduct again: the cart is empty now.
        $this->checkout($client)->assertSessionHasErrors('panier');
        $this->assertSame(7, $this->freshStock($product));
        $this->assertSame(1, Order::where('client_id', $client->id)->count());

        // And a cancelled/delivered status change never restocks or re-deducts.
        $reference = Order::where('client_id', $client->id)->value('reference');
        $this->actingAs($this->producer())
            ->post('/producer/orders/' . $reference . '/status', ['statut' => 'Annulée']);

        $this->assertSame(7, $this->freshStock($product)); // no invented restock
        $this->assertSame(3, (int) OrderItem::whereHas('order', fn ($q) => $q->where('client_id', $client->id))->sum('quantity'));
    }

    // 9. Service-level atomic guard ------------------------------------------------------
    public function test_9_guarded_decrement_rejects_more_than_available(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 3]);
        $this->fillCart($client, $product, 3);
        // Simulate a concurrent sale that happened inside the checkout window.
        Product::query()->where('id', $product->id)->update(['stock_quantity' => 2]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(OrderService::class)->placeOrder($client, [
            'nom' => 'Client Test',
            'telephone' => '655000112',
            'adresse' => 'Bafia, Cameroun',
            'moyen_paiement' => 'MTN Mobile Money',
        ]);
    }
}
