<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Checkout HTTP-level behaviour — now backed by the persistent MySQL orders
 * (Mission 9). Non destructive: DatabaseTransactions, never migrate:fresh.
 */
class CheckoutAndOrdersTest extends TestCase
{
    use DatabaseTransactions;

    private function makeProduct(array $overrides = []): Product
    {
        static $counter = 0;
        $counter++;

        $producer = User::factory()->create([
            'role' => 'producer',
            'email' => 'checkout-producer-' . uniqid() . '@example.test',
        ]);
        $category = Category::query()->first() ?? Category::create([
            'name' => 'Checkout Cat',
            'slug' => 'checkout-cat',
        ]);
        $location = Location::query()->first() ?? Location::create([
            'country' => 'Cameroun',
            'region' => 'Centre',
            'city' => 'Bafia',
        ]);

        return Product::create(array_merge([
            'producer_id' => $producer->id,
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => "Checkout Produit {$counter}",
            'slug' => "checkout-produit-{$counter}-" . uniqid(),
            'description' => 'Produit de test checkout.',
            'price' => 1500,
            'unit' => 'kg',
            'stock_quantity' => 10,
            'minimum_order' => 1,
            'is_available' => true,
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));
    }

    public function test_checkout_page_is_available_and_shows_summary(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $product = $this->makeProduct();

        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug);

        $this->actingAs($client)->get('/checkout')
            ->assertStatus(200)
            ->assertSee('Finaliser la commande')
            ->assertSee($product->name);
    }

    public function test_checkout_creates_a_persistent_order_and_redirects_to_history(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $product = $this->makeProduct(['price' => 2500]);
        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug, ['quantite' => 2]);

        $this->actingAs($client)->post('/checkout', [
            'nom' => 'Client Test',
            'telephone' => '655000112',
            'adresse' => 'Bafia, Cameroun',
            'moyen_paiement' => 'MTN Mobile Money',
        ])->assertRedirect(route('client.dashboard', ['tab' => 'orders']));

        $order = Order::query()->where('client_id', $client->id)->firstOrFail();
        $this->assertSame(5000.0, (float) $order->subtotal);
        $this->assertSame(6000.0, (float) $order->total);
        $this->assertStringStartsWith('CMD-', $order->reference);
    }

    public function test_empty_cart_cannot_open_checkout_or_create_order(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)->get('/checkout')->assertStatus(400);
        $this->actingAs($client)->post('/checkout', [
            'nom' => 'Client Test',
            'telephone' => '655000112',
            'adresse' => 'Bafia, Cameroun',
            'moyen_paiement' => 'MTN Mobile Money',
        ])->assertSessionHasErrors('panier');

        $this->assertSame(0, Order::count());
    }

    public function test_invalid_cart_product_cannot_open_checkout_or_create_order(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)
            ->withSession(['cart' => [
                'produit-inexistant' => ['nom' => 'Produit falsifié', 'prix_num' => 1, 'quantite' => 1],
            ]])
            ->get('/checkout')->assertStatus(400);

        $this->actingAs($client)
            ->post('/checkout', [
                'nom' => 'Client Test',
                'telephone' => '655000112',
                'adresse' => 'Bafia, Cameroun',
                'moyen_paiement' => 'MTN Mobile Money',
            ])->assertSessionHasErrors('panier');

        $this->assertSame(0, Order::count());
    }
}
