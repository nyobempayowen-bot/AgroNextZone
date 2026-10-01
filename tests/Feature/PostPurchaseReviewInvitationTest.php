<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProducerReview;
use App\Models\Review;
use App\Models\User;
use App\Services\PostPurchaseReviewService;
use App\Services\ProducerReviewService;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Notation STRICTEMENT liée à un achat réel.
 *
 * Invitation immédiate après achat confirmé, facultative, non bloquante,
 * et contrôle d'éligibilité intégralement refait côté serveur.
 */
class PostPurchaseReviewInvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $producer;
    private User $client;
    private User $otherClient;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = User::factory()->create(['role' => 'producer', 'name' => 'Ferme Socada']);
        $this->client = User::factory()->create(['role' => 'client', 'name' => 'Awa Njoya']);
        $this->otherClient = User::factory()->create(['role' => 'client', 'name' => 'Bob Tchouta']);

        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits-ppr']);
        $location = Location::create([
            'user_id' => $this->producer->id,
            'country' => 'Cameroun',
            'region' => 'Centre',
            'city' => 'Bafia',
            'is_primary' => true,
        ]);

        $this->product = $this->makeProduct($this->producer, $category->id, $location->id, 'Cacao', 'cacao-ppr');
    }

    private function makeProduct(User $producer, int $categoryId, int $locationId, string $name, string $slug): Product
    {
        return Product::create([
            'producer_id' => $producer->id,
            'category_id' => $categoryId,
            'location_id' => $locationId,
            'name' => $name,
            'slug' => $slug,
            'description' => 'Produit de test.',
            'price' => 1500,
            'unit' => 'kg',
            'stock_quantity' => 50,
            'minimum_order' => 1,
            'is_available' => true,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /** Commande « en attente » : simplement créée, paiement non abouti. */
    private function pendingOrder(User $client, Product $product, string $ref = 'PPR-PEND'): Order
    {
        $order = Order::create([
            'client_id' => $client->id,
            'reference' => $ref,
            'status' => 'pending',
            'payment_method' => 'MTN Mobile Money',
            'subtotal' => 1500, 'shipping_fee' => 0, 'total' => 1500,
            'shipping_name' => $client->name, 'shipping_phone' => '650000000',
            'shipping_address' => 'Bafia', 'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'producer_id' => $product->producer_id,
            'product_name' => $product->name,
            'unit_price' => 1500, 'unit' => 'kg', 'quantity' => 1, 'line_total' => 1500,
        ]);

        return $order;
    }

    /** Commande payée = achat réel (paiement « paid »). */
    private function paidOrder(User $client, Product $product, string $ref = 'PPR-PAID'): Order
    {
        $order = $this->pendingOrder($client, $product, $ref);
        $order->update(['status' => 'confirmed']);

        $order->payments()->create([
            'client_id' => $client->id,
            'method' => 'mtn_mobile_money',
            'status' => 'paid',
            'amount' => 1500,
            'provider' => 'mtn_momo_simulated',
            'provider_reference' => 'PAY-'.uniqid(),
            'paid_at' => now(),
        ]);

        return $order;
    }

    private function reviewPayload(array $overrides = []): array
    {
        return array_merge([
            'note' => 5,
            'titre' => 'Très bon produit',
            'commentaire' => 'Qualité conforme à la description, livraison rapide.',
        ], $overrides);
    }

    private function service(): PostPurchaseReviewService
    {
        return app(PostPurchaseReviewService::class);
    }

    /* =============== ÉTAPE 2 — ÉLIGIBILITÉ (1 à 6) =============== */

    /** 1. Client ayant acheté un produit → éligible. */
    public function test_client_who_bought_a_product_is_eligible(): void
    {
        $order = $this->paidOrder($this->client, $this->product);

        $invitation = $this->service()->invitationFor($order, $this->client);

        $this->assertSame(1, $invitation['products']->count());
        $this->assertSame($this->product->id, $invitation['products']->first()['id']);
        $this->assertNotNull(app(ReviewService::class)->purchasedOrder($this->client, $this->product));
    }

    /** 2. Client n'ayant jamais acheté → non éligible. */
    public function test_client_who_never_bought_is_not_eligible(): void
    {
        $order = $this->paidOrder($this->otherClient, $this->product);

        $this->assertSame(0, $this->service()->invitationFor($order, $this->client)['total']);
        $this->assertNull(app(ReviewService::class)->purchasedOrder($this->client, $this->product));
    }

    /** 3. Produit simplement consulté → non éligible. */
    public function test_merely_viewed_product_is_not_eligible(): void
    {
        $this->actingAs($this->client)->get(route('produit', ['slug' => $this->product->slug]))->assertOk();

        $this->assertDatabaseMissing('reviews', ['client_id' => $this->client->id]);
        $this->assertCount(0, $this->service()->pendingForClient($this->client));
    }

    /** 4. Produit uniquement ajouté au panier → non éligible. */
    public function test_product_only_added_to_cart_is_not_eligible(): void
    {
        $this->actingAs($this->client);
        $this->post(route('panier.ajouter', ['slug' => $this->product->slug]), ['quantity' => 1])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
        ]);

        $this->assertCount(0, $this->service()->pendingForClient($this->client));
        $this->assertNull(app(ReviewService::class)->purchasedOrder($this->client, $this->product));
    }

    /** 5. Commande non finalisée (paiement en attente) → non éligible. */
    public function test_non_finalized_order_is_not_eligible(): void
    {
        $order = $this->pendingOrder($this->client, $this->product);

        $this->assertSame(0, $this->service()->invitationFor($order, $this->client)['total']);
        $this->assertNull(app(ReviewService::class)->purchasedOrder($this->client, $this->product));
    }

    /** 6. Commande annulée → non éligible (statut métier existant). */
    public function test_cancelled_order_is_not_eligible(): void
    {
        $order = $this->paidOrder($this->client, $this->product);
        $order->update(['status' => 'cancelled']);

        $this->assertSame(0, $this->service()->invitationFor($order, $this->client)['total']);
        $this->assertNull(app(ReviewService::class)->purchasedOrder($this->client, $this->product));

        $check = app(ProducerReviewService::class)->canReview($this->client, $order, $this->producer);
        $this->assertFalse($check['eligible']);
    }

    /* =============== ÉTAPE 4/7 — INVITATION (7, 9, 10) =============== */

    /** 7. Achat confirmé → invitation de notation affichée. */
    public function test_confirmed_purchase_shows_rating_invitation(): void
    {
        $this->paidOrder($this->client, $this->product);

        $response = $this->actingAs($this->client)
            ->get(route('payment.show', ['reference' => 'PPR-PAID']));

        $response->assertOk()
            ->assertSee('Achat confirmé')
            ->assertSee('id="postPurchaseInvite"', false)
            ->assertSee('Noter maintenant')
            ->assertSee('Plus tard')
            ->assertSee('Cacao');
    }

    /** Aucune invitation tant que le paiement n'est pas confirmé. */
    public function test_no_invitation_while_payment_is_pending(): void
    {
        $this->pendingOrder($this->client, $this->product);

        $this->actingAs($this->client)
            ->get(route('payment.show', ['reference' => 'PPR-PEND']))
            ->assertOk()
            ->assertDontSee('id="postPurchaseInvite"', false);
    }

    /** 8. Clic « Noter maintenant » → formulaire correspondant. */
    public function test_rating_now_links_to_the_matching_rating_form(): void
    {
        $order = $this->paidOrder($this->client, $this->product);

        $response = $this->actingAs($this->client)
            ->get(route('payment.show', ['reference' => $order->reference]));

        // Le lien pointe vers l'onglet de notation, pré-sélectionné sur CETTE commande.
        // (le href est échappé en HTML : & devient &amp;)
        $response->assertSee(e(route('client.dashboard', ['tab' => 'reviews', 'order' => $order->id])), false);

        // ...et ce formulaire contient bien le produit acheté.
        $this->actingAs($this->client)
            ->get(route('client.dashboard', ['tab' => 'reviews']))
            ->assertOk()
            ->assertSee('Vos achats à évaluer')
            ->assertSee(route('produit.avis', ['slug' => $this->product->slug]));
    }

    /** 9. « Plus tard » → commande toujours valide, aucune notation créée. */
    public function test_postponing_keeps_order_valid_and_creates_nothing(): void
    {
        $order = $this->paidOrder($this->client, $this->product);

        // « Plus tard » n'envoie aucune requête : on vérifie l'état inchangé.
        $this->actingAs($this->client)->get(route('payment.show', ['reference' => $order->reference]))->assertOk();

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('paid', $order->payments()->first()->status);

        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('producer_reviews', 0);

        // Le client continue à naviguer normalement.
        $this->actingAs($this->client)->get(route('client.dashboard', ['tab' => 'orders']))->assertOk();
    }

    /** 10. Absence de notation → aucune erreur. */
    public function test_no_rating_at_all_is_not_an_error(): void
    {
        $this->paidOrder($this->client, $this->product);

        $this->actingAs($this->client)->get(route('client.dashboard', ['tab' => 'orders']))->assertOk();
        $this->actingAs($this->client)->get(route('client.dashboard', ['tab' => 'reviews']))->assertOk();
        $this->actingAs($this->client)->get(route('home'))->assertOk();

        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('producer_reviews', 0);
    }

    /** Étape 7 : retrouver plus tard les achats non notés. */
    public function test_client_can_find_pending_purchases_later(): void
    {
        $this->paidOrder($this->client, $this->product);

        $pending = $this->service()->pendingForClient($this->client);

        $this->assertCount(1, $pending);
        // 1 produit + 1 producteur = 2 éléments à noter.
        $this->assertSame(2, $this->service()->pendingCountFor($this->client));
        $this->assertSame(1, $pending->first()['products']->count());

        $this->actingAs($this->client)
            ->get(route('client.dashboard', ['tab' => 'reviews']))
            ->assertOk()
            ->assertSee('Cacao');
    }

    /* =============== ÉTAPE 6 — MULTI-PRODUITS (11) =============== */

    /** 11. Multi-produits → tous les produits éligibles sont proposés. */
    public function test_multi_product_order_proposes_every_eligible_item(): void
    {
        $category = Category::first();
        $location = Location::first();
        $second = $this->makeProduct($this->producer, $category->id, $location->id, 'Plantain', 'plantain-ppr');

        $order = $this->pendingOrder($this->client, $this->product, 'PPR-MULTI');
        $order->items()->create([
            'product_id' => $second->id,
            'producer_id' => $this->producer->id,
            'product_name' => $second->name,
            'unit_price' => 1500, 'unit' => 'kg', 'quantity' => 2, 'line_total' => 3000,
        ]);
        $order->update(['status' => 'confirmed']);
        Payment::create([
            'order_id' => $order->id, 'client_id' => $this->client->id,
            'method' => 'mtn_mobile_money', 'status' => 'paid', 'amount' => 4500,
            'provider' => 'mtn_momo_simulated', 'provider_reference' => 'PAY-MULTI', 'paid_at' => now(),
        ]);

        $invitation = $this->service()->invitationFor($order, $this->client);

        $this->assertSame(2, $invitation['products']->count());
        $this->assertEqualsCanonicalizing(
            [$this->product->id, $second->id],
            $invitation['products']->pluck('id')->all()
        );

        // L'invitation affiche les deux produits, pas une notation ambiguë.
        $this->actingAs($this->client)
            ->get(route('payment.show', ['reference' => 'PPR-MULTI']))
            ->assertOk()
            ->assertSee('Cacao')
            ->assertSee('Plantain');

        // L'utilisateur peut n'en noter qu'un : l'autre reste proposé.
        $this->actingAs($this->client)->post(
            route('produit.avis', ['slug' => $this->product->slug]),
            $this->reviewPayload()
        );

        $remaining = $this->service()->invitationFor($order->fresh(), $this->client);
        $this->assertSame(1, $remaining['products']->count());
        $this->assertSame($second->id, $remaining['products']->first()['id']);
    }

    /* =============== ÉTAPE 9 — PRODUCTEUR (12, 13) =============== */

    /** 12. Producteur réellement acheté → notation producteur possible. */
    public function test_really_purchased_producer_can_be_rated(): void
    {
        $order = $this->paidOrder($this->client, $this->product);

        $check = app(ProducerReviewService::class)->canReview($this->client, $order, $this->producer);
        $this->assertTrue($check['eligible']);
        $this->assertSame(1, $this->service()->invitationFor($order, $this->client)['producers']->count());
    }

    /** 13. Producteur jamais acheté → notation refusée. */
    public function test_producer_never_bought_is_refused(): void
    {
        $stranger = User::factory()->create(['role' => 'producer', 'name' => 'Producteur C']);
        $strangerProduct = $this->makeProduct($stranger, Category::first()->id, Location::first()->id, 'Mangue', 'mangue-ppr');

        $order = $this->paidOrder($this->client, $this->product);

        $check = app(ProducerReviewService::class)->canReview($this->client, $order, $stranger);
        $this->assertFalse($check['eligible']);

        $this->assertDatabaseMissing('producer_reviews', ['producer_id' => $stranger->id]);

        // Un POST direct est refusé lui aussi.
        $this->actingAs($this->client)->post(
            route('client.producer-review.store', ['order' => $order->id, 'producer' => $stranger->id]),
            ['rating' => 5]
        )->assertSessionHasErrors('note');

        $this->assertDatabaseMissing('producer_reviews', ['producer_id' => $stranger->id]);
    }

    /* =============== ÉTAPE 8 — FRAUDE / IDOR (14 à 17) =============== */

    /** 14. Manipulation de product_id → refus. */
    public function test_tampering_with_product_id_is_refused(): void
    {
        $category = Category::first();
        $location = Location::first();
        $notPurchased = $this->makeProduct($this->producer, $category->id, $location->id, 'Colombo', 'colombo-ppr');

        $this->paidOrder($this->client, $this->product);

        // Le client tente de noter un produit qu'il n'a jamais acheté,
        // en postant directement sur la route avec cet autre slug.
        $this->actingAs($this->client)->post(
            route('produit.avis', ['slug' => $notPurchased->slug]),
            $this->reviewPayload()
        )->assertSessionHasErrors('avis');

        $this->assertDatabaseMissing('reviews', ['product_id' => $notPurchased->id]);
    }

    /** 15. Manipulation de order_id → refus. */
    public function test_tampering_with_order_id_is_refused(): void
    {
        $orderB = $this->paidOrder($this->otherClient, $this->product, 'PPR-BOB');
        $this->paidOrder($this->client, $this->product, 'PPR-AWA');

        // Awa tente de noter le producteur via la commande de Bob.
        // Le contrôle d'ownership est fait dans le contrôleur : 403 net.
        $this->actingAs($this->client)->post(
            route('client.producer-review.store', ['order' => $orderB->id, 'producer' => $this->producer->id]),
            ['rating' => 5]
        )->assertForbidden();

        $this->assertDatabaseCount('producer_reviews', 0);
    }

    /** 16. Utilisateur A tente de noter avec la commande de B → refus. */
    public function test_client_cannot_rate_through_another_clients_order(): void
    {
        $orderB = $this->paidOrder($this->otherClient, $this->product, 'PPR-BOB2');

        $this->actingAs($this->client)->post(
            route('client.producer-review.store', ['order' => $orderB->id, 'producer' => $this->producer->id]),
            ['rating' => 4]
        )->assertForbidden();

        $this->assertDatabaseCount('producer_reviews', 0);
    }

    /** 17. Notation envoyée directement par HTTP sans achat → refus. */
    public function test_direct_http_post_without_purchase_is_refused(): void
    {
        $this->actingAs($this->client)->post(
            route('produit.avis', ['slug' => $this->product->slug]),
            $this->reviewPayload()
        )->assertSessionHasErrors('avis');

        $this->assertDatabaseCount('reviews', 0);
    }

    /** Un producteur ne peut jamais noter. */
    public function test_producer_cannot_post_a_review(): void
    {
        $this->paidOrder($this->client, $this->product);

        $this->actingAs($this->producer)->post(
            route('produit.avis', ['slug' => $this->product->slug]),
            $this->reviewPayload()
        )->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    /* =============== DOUBLON & BORNES (18, 19, 20) =============== */

    /** 18. Double notation → refusée (règle métier existante : 1 avis / client / produit). */
    public function test_double_rating_is_refused(): void
    {
        $this->paidOrder($this->client, $this->product);

        $this->actingAs($this->client)->post(
            route('produit.avis', ['slug' => $this->product->slug]),
            $this->reviewPayload(['note' => 4])
        );

        $this->actingAs($this->client)->post(
            route('produit.avis', ['slug' => $this->product->slug]),
            $this->reviewPayload(['note' => 1])
        )->assertSessionHasErrors('avis');

        // Le produit n'est plus proposé ; le producteur, si.
        $this->assertSame(0, $this->service()->pendingForClient($this->client)->sum(fn ($p) => $p['products']->count()));
        $this->assertSame(1, Review::where('product_id', $this->product->id)->count());
    }

    /** 19. Notes 1 à 5 → acceptées. */
    public function test_ratings_from_1_to_5_are_accepted(): void
    {
        foreach ([1, 2, 3, 4, 5] as $note) {
            $client = User::factory()->create(['role' => 'client']);
            $this->paidOrder($client, $this->product, 'PPR-N'.$note);

            $this->actingAs($client)->post(
                route('produit.avis', ['slug' => $this->product->slug]),
                $this->reviewPayload(['note' => $note])
            );

            $this->assertDatabaseHas('reviews', [
                'product_id' => $this->product->id,
                'client_id' => $client->id,
                'rating' => $note,
            ]);
        }
    }

    /** 20. Note 0 ou 6 → refusées. */
    public function test_ratings_below_1_or_above_5_are_refused(): void
    {
        $this->paidOrder($this->client, $this->product);

        foreach ([0, 6, -1, 99] as $bad) {
            $this->actingAs($this->client)->post(
                route('produit.avis', ['slug' => $this->product->slug]),
                $this->reviewPayload(['note' => $bad])
            )->assertSessionHasErrors('note');
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    /** Notation producteur : bornes 1..5 également contrôlées. */
    public function test_producer_rating_bounds_are_enforced(): void
    {
        $order = $this->paidOrder($this->client, $this->product);

        foreach ([0, 6] as $bad) {
            $this->actingAs($this->client)->post(
                route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]),
                ['rating' => $bad]
            )->assertSessionHasErrors('rating');
        }

        $this->assertDatabaseCount('producer_reviews', 0);

        $this->actingAs($this->client)->post(
            route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]),
            ['rating' => 5]
        );

        $this->assertDatabaseHas('producer_reviews', [
            'order_id' => $order->id,
            'client_id' => $this->client->id,
            'producer_id' => $this->producer->id,
            'rating' => 5,
        ]);
    }

    /** Un produit déjà noté n'apparaît plus dans les invitations. */
    public function test_already_reviewed_product_leaves_the_invitation(): void
    {
        $order = $this->paidOrder($this->client, $this->product);

        Review::create([
            'product_id' => $this->product->id,
            'client_id' => $this->client->id,
            'order_id' => $order->id,
            'rating' => 5,
            'title' => 'Très bien',
            'comment' => 'Parfait.',
            'is_verified_purchase' => true,
            'status' => 'published',
        ]);

        $this->assertSame(0, $this->service()->invitationFor($order, $this->client)['products']->count());
    }

    /** La confirmation de commande n'est jamais bloquée par la notation. */
    public function test_payment_confirmation_is_never_blocked(): void
    {
        $order = $this->pendingOrder($this->client, $this->product);

        // Paiement « en attente », comme après un checkout réel.
        $order->payments()->create([
            'client_id' => $this->client->id,
            'method' => 'mtn_mobile_money',
            'status' => 'pending',
            'amount' => 1500,
            'provider' => 'mtn_momo_simulated',
            'provider_reference' => 'PAY-DECIDE',
        ]);

        $this->actingAs($this->client)
            ->get(route('payment.decide', ['reference' => $order->reference, 'outcome' => 'successful']))
            ->assertRedirect(route('payment.show', ['reference' => $order->reference]));

        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseCount('producer_reviews', 0);
    }

    /** Un client non connecté ne voit aucune invitation. */
    public function test_guest_sees_no_invitation(): void
    {
        $this->paidOrder($this->client, $this->product);

        $this->get(route('payment.show', ['reference' => 'PPR-PAID']))->assertRedirect(route('login'));
        $this->get(route('client.dashboard', ['tab' => 'reviews']))->assertRedirect(route('login'));
    }
}