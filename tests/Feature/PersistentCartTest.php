<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Mission 8 — persistent MySQL cart.
 *
 * Uses DatabaseTransactions (NOT RefreshDatabase) so no destructive command
 * (migrate:fresh / refresh / wipe) is ever executed. Each test writes inside a
 * transaction that is rolled back afterwards.
 */
class PersistentCartTest extends TestCase
{
    use DatabaseTransactions;

    private ?User $producer = null;

    private function producer(): User
    {
        return $this->producer ??= User::factory()->create([
            'role' => 'producer',
            'email' => 'mission8-producer@example.test',
        ]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        static $counter = 0;
        $counter++;

        $category = Category::query()->first() ?? Category::create([
            'name' => 'Mission8 Cat',
            'slug' => 'mission8-cat',
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
            'name' => "Mission8 Produit {$counter}",
            'slug' => "mission8-produit-{$counter}-" . uniqid(),
            'description' => 'Produit de test Mission 8.',
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
            'email' => 'mission8-client-' . uniqid() . '@example.test',
        ]);
    }

    // 1. Création du panier --------------------------------------------------
    public function test_1_cart_row_is_created_on_first_add(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();

        $this->actingAs($client)
            ->post('/panier/ajouter/' . $product->slug)
            ->assertRedirect('/panier');

        $this->assertDatabaseHas('carts', ['user_id' => $client->id]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => Cart::where('user_id', $client->id)->value('id'),
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    // 2. Ajout ---------------------------------------------------------------
    public function test_2_client_can_add_product_to_persistent_cart(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();

        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug);

        $this->assertSame(1, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $client->id))->count());
    }

    // 3. Doublon -------------------------------------------------------------
    public function test_3_adding_same_product_twice_increments_quantity_without_duplicate_row(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 5]);

        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug);
        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug);

        $items = CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $client->id))->get();

        $this->assertCount(1, $items);
        $this->assertSame(2, $items->first()->quantity);
    }

    // 4. Quantité ------------------------------------------------------------
    public function test_4_add_with_explicit_quantity_is_persisted(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 8]);

        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug, ['quantite' => 3]);

        $this->assertSame(3, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $client->id))->value('quantity'));
    }

    // 5. Modification --------------------------------------------------------
    public function test_5_quantity_can_be_updated(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 9]);

        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug, ['quantite' => 2]);
        $this->actingAs($client)
            ->post('/panier/quantite/' . $product->slug, ['quantite' => 7])
            ->assertRedirect('/panier');

        $this->assertSame(7, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $client->id))->value('quantity'));
    }

    // 6. Suppression ---------------------------------------------------------
    public function test_6_line_can_be_removed(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();

        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug);
        $this->actingAs($client)->post('/panier/supprimer/' . $product->slug);

        $this->assertSame(0, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $client->id))->count());
        // The cart row itself survives (only its items are removed).
        $this->assertDatabaseHas('carts', ['user_id' => $client->id]);
    }

    // 7. Vidage --------------------------------------------------------------
    public function test_7_clearing_cart_removes_every_line(): void
    {
        $client = $this->client();
        $first = $this->makeProduct();
        $second = $this->makeProduct();

        $this->actingAs($client)->post('/panier/ajouter/' . $first->slug);
        $this->actingAs($client)->post('/panier/ajouter/' . $second->slug);
        $this->actingAs($client)->post('/panier/vider')->assertRedirect('/panier');

        $this->assertSame(0, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $client->id))->count());
    }

    // 8. Total ---------------------------------------------------------------
    public function test_8_total_is_computed_server_side_from_mysql_prices(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['price' => 1250, 'stock_quantity' => 6]);

        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug, ['quantite' => 4]);

        $response = $this->actingAs($client)->get('/panier');
        $response->assertOk();

        // 1250 * 4 = 5000, plus the 1000 shipping fee displayed by the view.
        $response->assertSee('5 000 FCFA', false);

        $cart = Cart::where('user_id', $client->id)->firstOrFail();
        $this->assertSame(5000.0, $cart->subtotal());
    }

    public function test_8b_browser_supplied_price_is_ignored(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['price' => 1250, 'stock_quantity' => 6]);

        // A malicious client tries to inject its own price.
        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug, [
            'quantite' => 2,
            'prix_num' => 1,
            'prix' => '1 FCFA',
        ]);

        $cart = Cart::where('user_id', $client->id)->firstOrFail();
        $this->assertSame(2500.0, $cart->subtotal());
    }

    // 9. Persistance ---------------------------------------------------------
    public function test_9_cart_survives_logout_and_relogin(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 4]);

        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug, ['quantite' => 3]);

        // Logout invalidates the session entirely.
        $this->actingAs($client)->post('/logout');
        $this->assertGuest();

        // Relogin and read the same cart back from MySQL.
        $this->actingAs($client)->get('/panier')
            ->assertOk()
            ->assertSee($product->name);

        $this->assertSame(3, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $client->id))->value('quantity'));
    }

    public function test_9b_cart_is_visible_from_a_fresh_session(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();

        Cart::create(['user_id' => $client->id])->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // New request cycle, empty session: the cart still comes from MySQL.
        $this->flushSession();
        $this->actingAs($client)->get('/panier')
            ->assertOk()
            ->assertSee($product->name);
    }

    // 10. Sécurité -----------------------------------------------------------
    public function test_10_client_cannot_reach_another_clients_cart(): void
    {
        $owner = $this->client();
        $intruder = $this->client();
        $product = $this->makeProduct();

        $this->actingAs($owner)->post('/panier/ajouter/' . $product->slug, ['quantite' => 2]);

        // The intruder sees an empty cart and cannot remove/alter the owner's line.
        $this->actingAs($intruder)->get('/panier')->assertOk()->assertDontSee($product->name);
        $this->actingAs($intruder)->post('/panier/supprimer/' . $product->slug);
        $this->actingAs($intruder)->post('/panier/quantite/' . $product->slug, ['quantite' => 9]);
        $this->actingAs($intruder)->post('/panier/vider');

        $this->assertSame(2, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $owner->id))->value('quantity'));
        $this->assertSame(0, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $intruder->id))->count());
    }

    public function test_10b_guest_cannot_access_cart_endpoints(): void
    {
        $product = $this->makeProduct();

        $this->get('/panier')->assertUnauthorized();
        $this->post('/panier/ajouter/' . $product->slug)->assertUnauthorized();
        $this->post('/panier/quantite/' . $product->slug, ['quantite' => 2])->assertUnauthorized();
        $this->post('/panier/supprimer/' . $product->slug)->assertUnauthorized();
        $this->post('/panier/vider')->assertUnauthorized();
    }

    public function test_10c_cart_endpoints_are_protected_by_csrf(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();

        // The web group applies CSRF protection to every cart route.
        // Laravel 13 ships it as PreventRequestForgery (successor of VerifyCsrfToken).
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $reflection = new \ReflectionClass($kernel);
        $property = $reflection->getProperty('middlewareGroups');
        $property->setAccessible(true);
        $middleware = $property->getValue($kernel)['web'] ?? [];

        $csrf = array_values(array_filter(
            $middleware,
            static fn ($m) => is_string($m) && str_contains($m, 'PreventRequestForgery')
        ));
        $this->assertNotEmpty($csrf, 'CSRF middleware missing from the web group');

        foreach (['panier.ajouter', 'panier.quantite', 'panier.supprimer', 'panier.vider'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, "route {$name} missing");
            $this->assertContains('web', $route->middleware());
        }

        // In real HTTP, a token-less POST is rejected with 419 before reaching the
        // controller. The framework relaxes CSRF while running tests, so we assert the
        // protective middleware on the route instead of the 419 response here.
        $roleMiddleware = app('router')->getRoutes()->getByName('panier.ajouter')->middleware();
        $this->assertTrue(
            collect($roleMiddleware)->contains(static fn ($m) => is_string($m) && str_starts_with($m, 'role:') && str_contains($m, 'client')),
            'cart routes must stay behind the client role middleware: ' . implode(', ', $roleMiddleware)
        );
    }

    // 11. Producteur interdit -------------------------------------------------
    public function test_11_producer_is_forbidden_on_every_cart_action(): void
    {
        $producer = $this->producer();
        $product = $this->makeProduct();

        $this->actingAs($producer)->get('/panier')->assertForbidden();
        $this->actingAs($producer)->post('/panier/ajouter/' . $product->slug)->assertForbidden();
        $this->actingAs($producer)->post('/panier/quantite/' . $product->slug, ['quantite' => 2])->assertForbidden();
        $this->actingAs($producer)->post('/panier/supprimer/' . $product->slug)->assertForbidden();
        $this->actingAs($producer)->post('/panier/vider')->assertForbidden();

        $this->assertSame(0, Cart::where('user_id', $producer->id)->count());
    }

    // 12. Produit inexistant --------------------------------------------------
    public function test_12_unknown_product_is_rejected(): void
    {
        $client = $this->client();

        $this->actingAs($client)
            ->post('/panier/ajouter/mission8-produit-inexistant')
            ->assertSessionHasErrors('produit');

        $this->assertSame(0, CartItem::count());
    }

    // 13. Quantité invalide ---------------------------------------------------
    public function test_13_invalid_quantities_are_rejected(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();

        $this->actingAs($client)
            ->post('/panier/ajouter/' . $product->slug, ['quantite' => 0])
            ->assertSessionHasErrors('quantite');

        $this->actingAs($client)
            ->post('/panier/ajouter/' . $product->slug, ['quantite' => 'abc'])
            ->assertSessionHasErrors('quantite');

        $this->actingAs($client)
            ->post('/panier/ajouter/' . $product->slug, ['quantite' => 999])
            ->assertSessionHasErrors('quantite');

        $this->assertSame(0, CartItem::count());
    }

    public function test_13b_quantity_beyond_stock_is_rejected(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 3]);

        $this->actingAs($client)
            ->post('/panier/ajouter/' . $product->slug, ['quantite' => 4])
            ->assertSessionHasErrors('quantite');

        $this->assertSame(0, CartItem::count());
    }

    // 14. Produit indisponible ------------------------------------------------
    public function test_14_unavailable_or_unpublished_products_cannot_be_added(): void
    {
        $client = $this->client();
        $outOfStock = $this->makeProduct(['stock_quantity' => 0]);
        $unavailable = $this->makeProduct(['is_available' => false]);
        $draft = $this->makeProduct(['status' => 'draft']);

        foreach ([$outOfStock, $unavailable, $draft] as $product) {
            $this->actingAs($client)
                ->post('/panier/ajouter/' . $product->slug)
                ->assertSessionHasErrors('produit');
        }

        $this->assertSame(0, CartItem::count());
    }

    // 15. Migration progressive de l'ancien panier session --------------------
    public function test_15_legacy_session_cart_is_imported_once_then_dropped(): void
    {
        $client = $this->client();
        $product = $this->makeProduct(['stock_quantity' => 5]);

        $this->actingAs($client)
            ->withSession(['cart' => [
                $product->slug => ['nom' => 'Ancien panier', 'prix_num' => 1, 'quantite' => 2],
            ]])
            ->get('/panier')
            ->assertOk()
            ->assertSessionMissing('cart');

        $this->assertSame(2, CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $client->id))->value('quantity'));
    }

    // 16. Compteur d'accueil --------------------------------------------------
    public function test_16_header_counter_reads_the_persistent_cart(): void
    {
        $client = $this->client();
        $product = $this->makeProduct();

        $this->actingAs($client)->post('/panier/ajouter/' . $product->slug, ['quantite' => 3]);

        $this->actingAs($client)->get('/')
            ->assertOk()
            ->assertSee('data-cart-count', false);
    }
}
