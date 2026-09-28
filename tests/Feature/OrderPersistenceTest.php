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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Mission 9 — persistent MySQL orders.
 *
 * Uses DatabaseTransactions (NOT RefreshDatabase / migrate:fresh) so no
 * destructive command is ever executed against the shared MySQL instance.
 * Each test writes inside a transaction that is rolled back afterwards.
 */
class OrderPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    private ?User $producer = null;

    private function producer(): User
    {
        return $this->producer ??= User::factory()->create([
            'role' => 'producer',
            'email' => 'mission9-producer@example.test',
        ]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        static $counter = 0;
        $counter++;

        $category = Category::query()->first() ?? Category::create([
            'name' => 'Mission9 Cat',
            'slug' => 'mission9-cat',
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
            'name' => "Mission9 Produit {$counter}",
            'slug' => "mission9-produit-{$counter}-" . uniqid(),
            'description' => 'Produit de test Mission 9.',
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
            'email' => 'mission9-client-' . uniqid() . '@example.test',
        ]);
    }

    /**
     * Fill the persistent MySQL cart of a client through the public API.
     */
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

    // 1. Création -------------------------------------------------------------
    public function test_1_order_is_created_and_belongs_to_the_client(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['price' => 2000]);
        $this->fillCart($client, $product, 2);

        $this->checkout($client)->assertRedirect();

        $order = Order::query()->where('client_id', $client->id)->firstOrFail();
        $this->assertSame('preparing', $order->status);
        $this->assertNotNull($order->placed_at);
        $this->assertNotNull($order->reference);
    }

    // 2. Items ----------------------------------------------------------------
    public function test_2_order_contains_its_order_items(): void
    {
        $client = $this->client();
        $first = $this->makeProduct(['price' => 1500]);
        $second = $this->makeProduct(['price' => 900, 'unit' => 'régime']);
        $this->fillCart($client, $first, 2);
        $this->fillCart($client, $second, 1);

        $this->checkout($client);

        $order = Order::query()->where('client_id', $client->id)->firstOrFail();
        $items = OrderItem::query()->where('order_id', $order->id)->orderBy('id')->get();

        $this->assertCount(2, $items);
        $this->assertSame($first->name, $items[0]->product_name);
        $this->assertSame(2, $items[0]->quantity);
        $this->assertSame(1500.0, (float) $items[0]->unit_price);
        $this->assertSame(3000.0, (float) $items[0]->line_total);
        $this->assertSame($second->name, $items[1]->product_name);
        $this->assertSame($second->unit, $items[1]->unit);
        $this->assertSame($this->producer()->id, (int) $items[0]->producer_id);
    }

    // 3. Total ----------------------------------------------------------------
    public function test_3_total_is_subtotal_plus_shipping_computed_from_mysql_prices(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['price' => 1250]);
        $this->fillCart($client, $product, 4);

        $this->checkout($client);

        $order = Order::query()->where('client_id', $client->id)->firstOrFail();
        $this->assertSame(5000.0, (float) $order->subtotal); // 1250 * 4, MySQL price only
        $this->assertSame(1000.0, (float) $order->shipping_fee);
        $this->assertSame(6000.0, (float) $order->total);
    }

    // 4. Prix historique ------------------------------------------------------
    public function test_4_line_prices_are_frozen_and_survive_later_product_price_changes(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['price' => 2400]);
        $this->fillCart($client, $product, 3);

        $this->checkout($client);

        // The producer later changes the MySQL price of the product.
        $product->update(['price' => 9999]);

        $item = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('client_id', $client->id))
            ->firstOrFail();

        // History is untouched: the snapshot keeps the price at order time.
        $this->assertSame(2400.0, (float) $item->unit_price);
        $this->assertSame(7200.0, (float) $item->line_total);
        $this->assertSame($product->name, $item->product_name); // snapshot name kept
    }

    // 5. Persistance ----------------------------------------------------------
    public function test_5_order_persists_after_logout_and_in_a_fresh_session(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();
        $this->fillCart($client, $product, 1);
        $this->checkout($client);

        $this->actingAs($client)->post('/logout');
        $this->actingAs($client)->get('/client/dashboard?tab=orders')
            ->assertOk()
            ->assertSee($product->name);

        $this->assertSame(1, Order::where('client_id', $client->id)->count());
    }

    public function test_5b_legacy_session_order_keys_are_drained_not_used(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();
        $this->fillCart($client, $product, 1);

        $this->actingAs($client)
            ->withSession([
                'orders' => [['id' => 'CMD-LEGACY', 'total' => 1]],
                'producer_orders' => [['id' => 'CMD-LEGACY']],
            ])
            ->post('/checkout', [
                'nom' => 'Client Test',
                'telephone' => '655000112',
                'adresse' => 'Bafia, Cameroun',
                'moyen_paiement' => 'MTN Mobile Money',
            ]);

        $session = app('session.store');
        $this->assertFalse($session->has('orders'));
        $this->assertFalse($session->has('producer_orders'));
        $this->assertDatabaseMissing('orders', ['reference' => 'CMD-LEGACY']);
        $this->assertSame(1, Order::where('client_id', $client->id)->count());
    }

    // 6. Visibilité client ----------------------------------------------------
    public function test_6_client_sees_only_his_own_orders(): void
    {
        $client = $this->client();
        $other = $this->client();
        $product = $this->makeProduct();

        $this->fillCart($client, $product, 1);
        $this->checkout($client);

        $this->fillCart($other, $product, 1);
        $this->checkout($other);

        $response = $this->actingAs($client)->get('/client/dashboard?tab=orders');
        $response->assertOk();

        $own = Order::where('client_id', $client->id)->firstOrFail();
        $foreign = Order::where('client_id', $other->id)->firstOrFail();

        $response->assertSee($own->reference)->assertDontSee($foreign->reference);
        $this->assertSame([$own->id], Order::where('client_id', $client->id)->pluck('id')->all());
    }

    // 7. Visibilité producteur ------------------------------------------------
    public function test_7_producer_sees_only_orders_containing_his_products(): void
    {
        $ownProducer = $this->producer();
        $otherProducer = User::factory()->create([
            'role' => 'producer',
            'email' => 'mission9-other-producer-' . uniqid() . '@example.test',
        ]);
        $ownProduct = $this->makeProduct();
        $otherProduct = $this->makeProduct(['producer_id' => $otherProducer->id]);

        $client = $this->client();
        $this->fillCart($client, $ownProduct, 2);
        $this->fillCart($client, $otherProduct, 1);
        $this->checkout($client);

        $order = Order::query()->where('client_id', $client->id)->firstOrFail();

        $service = app(\App\Services\OrderService::class);
        $visible = $service->ordersForProducer($ownProducer);

        $this->assertSame([$order->id], $visible->pluck('id')->all());
        // The producer view exposes only HIS lines of the order.
        $view = $service->toProducerView($visible->first());
        $this->assertSame($ownProduct->name, $view['produit']);

        // And the producer sees it on his dashboard.
        $this->actingAs($ownProducer)->get('/producer/dashboard?tab=orders')
            ->assertOk()
            ->assertSee($order->reference);
    }

    // 8. Isolation entre utilisateurs -----------------------------------------
    public function test_8_orders_are_isolated_between_users(): void
    {
        $clientA = $this->client();
        $clientB = $this->client();
        $product = $this->makeProduct();

        $this->fillCart($clientA, $product, 1);
        $this->checkout($clientA);
        $this->fillCart($clientB, $product, 1);
        $this->checkout($clientB);

        // A's dashboard never shows B's reference.
        $refA = Order::where('client_id', $clientA->id)->value('reference');
        $refB = Order::where('client_id', $clientB->id)->value('reference');

        $this->actingAs($clientA)->get('/client/dashboard?tab=orders')
            ->assertOk()
            ->assertSee($refA)
            ->assertDontSee($refB);

        $this->actingAs($clientB)->get('/client/dashboard?tab=orders')
            ->assertOk()
            ->assertSee($refB)
            ->assertDontSee($refA);
    }

    // 9. Accès interdit (IDOR) ------------------------------------------------
    public function test_9a_client_cannot_read_another_clients_order(): void
    {
        $owner = $this->client();
        $intruder = $this->client();
        $product = $this->makeProduct();
        $this->fillCart($owner, $product, 1);
        $this->checkout($owner);

        $order = Order::where('client_id', $owner->id)->firstOrFail();

        // No public endpoint lets a client open a foreign order; the scoped
        // service is the gate — assert it filters by client_id.
        $service = app(\App\Services\OrderService::class);
        $visible = $service->ordersForClient($intruder);
        $this->assertNull($visible->firstWhere('id', $order->id));
    }

    public function test_9b_producer_without_own_product_in_the_order_is_forbidden(): void
    {
        $client = $this->client();
        $stranger = User::factory()->create([
            'role' => 'producer',
            'email' => 'mission9-stranger-' . uniqid() . '@example.test',
        ]);
        $product = $this->makeProduct(['producer_id' => $this->producer()->id]);
        $this->fillCart($client, $product, 1);
        $this->checkout($client);

        $order = Order::where('client_id', $client->id)->firstOrFail();

        $this->actingAs($stranger)
            ->post('/producer/orders/' . $order->reference . '/status', ['statut' => 'Livrée'])
            ->assertForbidden();

        $this->assertSame('preparing', $order->refresh()->status);
    }

    public function test_9c_client_cannot_use_the_producer_status_endpoint(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();
        $this->fillCart($client, $product, 1);
        $this->checkout($client);

        $reference = Order::where('client_id', $client->id)->value('reference');

        $this->actingAs($client)
            ->post('/producer/orders/' . $reference . '/status', ['statut' => 'Livrée'])
            ->assertForbidden();
    }

    public function test_9d_producer_can_update_status_of_his_own_order(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['producer_id' => $this->producer()->id]);
        $this->fillCart($client, $product, 1);
        $this->checkout($client);

        $reference = Order::where('client_id', $client->id)->value('reference');

        $this->actingAs($this->producer())
            ->post('/producer/orders/' . $reference . '/status', ['statut' => 'Livrée'])
            ->assertRedirect();

        $this->assertSame('delivered', Order::where('reference', $reference)->value('status'));
    }

    // 10. Panier lié ----------------------------------------------------------
    public function test_10_cart_is_emptied_after_the_order_is_created(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();
        $this->fillCart($client, $product, 3);

        $cartId = Cart::where('user_id', $client->id)->value('id');

        $this->checkout($client);

        $this->assertSame(0, CartItem::where('cart_id', $cartId)->count());
        $this->assertSame(3, (int) OrderItem::whereHas('order', fn ($q) => $q->where('client_id', $client->id))->sum('quantity'));
    }

    // 11. Panier vide ---------------------------------------------------------
    public function test_11_empty_cart_cannot_create_an_order(): void
    {
        $client = $this->client();

        $this->checkout($client)->assertSessionHasErrors('panier');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
    }

    // 12. Rôles ---------------------------------------------------------------
    public function test_12_producer_cannot_place_an_order(): void
    {
        $producer = $this->producer();
        $product = $this->makeProduct();

        $this->actingAs($producer)
            ->post('/checkout', [
                'nom' => 'Producer',
                'telephone' => '655000112',
                'adresse' => 'Bafia',
                'moyen_paiement' => 'MTN Mobile Money',
            ])
            ->assertForbidden();

        $this->assertSame(0, Order::count());
    }

    // 13. Historique d'expédition --------------------------------------------
    public function test_13_shipping_details_are_kept_on_the_order(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();
        $this->fillCart($client, $product, 1);

        $this->checkout($client, [
            'nom' => 'Amina Ndiaye',
            'telephone' => '677112233',
            'adresse' => 'Mendong, Yaoundé',
            'moyen_paiement' => 'Orange Money',
        ]);

        $order = Order::where('client_id', $client->id)->firstOrFail();
        $this->assertSame('Amina Ndiaye', $order->shipping_name);
        $this->assertSame('677112233', $order->shipping_phone);
        $this->assertSame('Mendong, Yaoundé', $order->shipping_address);
        $this->assertSame('Orange Money', $order->payment_method);
    }
}
