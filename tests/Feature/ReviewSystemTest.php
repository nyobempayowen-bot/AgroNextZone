<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Système d'avis produits — cahier des charges complet.
 *
 * Couvre :
 *  1. avis valide après achat  -> accepté
 *  2. utilisateur sans achat    -> refusé
 *  3. note 0 / 6 / manquante    -> refusée
 *  4. commentaire invalide      -> refusé
 *  5. doublon d'avis            -> interdit
 *  6. auto-évaluation           -> interdite
 *  7. update / delete d'un avis d'autrui -> 403
 *  8. accès direct non autorisé -> refusé
 *  9. affichage des vrais avis + « Achat vérifié »
 * 10. produit sans avis         -> affichage propre
 *
 * MySQL reste la source de vérité : aucun cas de test ne s'appuie sur la session.
 */
class ReviewSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $producer;
    private User $client;
    private User $otherClient;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = User::factory()->create(['role' => 'producer', 'name' => 'Ferme Test']);
        $this->client = User::factory()->create(['role' => 'client', 'name' => 'Awa Njoya']);
        $this->otherClient = User::factory()->create(['role' => 'client', 'name' => 'Bob Tchouta']);

        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits-rev']);
        $location = Location::create([
            'user_id' => $this->producer->id,
            'country' => 'Cameroun',
            'region' => 'Centre',
            'city' => 'Bafia',
            'is_primary' => true,
        ]);

        $this->product = Product::create([
            'producer_id' => $this->producer->id,
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => 'Banane reviews',
            'slug' => 'banane-reviews',
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

    /** Crée une commande payée contenant le produit : achat réel. */
    private function paidOrder(User $client): Order
    {
        $order = Order::create([
            'client_id' => $client->id,
            'reference' => 'CMD-REV-'.strtoupper(substr(md5($client->email), 0, 6)),
            'status' => 'confirmed',
            'payment_method' => 'MTN Mobile Money',
            'subtotal' => 1500,
            'shipping_fee' => 0,
            'total' => 1500,
            'shipping_name' => $client->name,
            'shipping_phone' => '650000000',
            'shipping_address' => 'Bafia',
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'producer_id' => $this->producer->id,
            'product_name' => $this->product->name,
            'unit_price' => 1500,
            'unit' => 'kg',
            'quantity' => 1,
            'line_total' => 1500,
        ]);

        $order->payments()->create([
            'client_id' => $client->id,
            'method' => 'mtn_mobile_money',
            'status' => 'paid',
            'amount' => 1500,
            'provider' => 'mtn_momo_simulated',
            'provider_reference' => 'PAY-REV-'.substr(md5($client->email), 0, 8),
            'paid_at' => now(),
        ]);

        return $order;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'note' => 5,
            'titre' => 'Très bons produits',
            'commentaire' => 'Réception rapide et produits de très bonne qualité.',
        ], $overrides);
    }

    /* ------------------------ 1. Cas nominal ------------------------ */

    public function test_valid_review_after_real_purchase_is_accepted(): void
    {
        $this->paidOrder($this->client);

        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload())
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $this->product->id,
            'client_id' => $this->client->id,
            'rating' => 5,
            'is_verified_purchase' => true,
            'status' => 'published',
        ]);
    }

    /* ------------------- 2. Achat réellement requis ------------------- */

    public function test_client_without_purchase_cannot_review(): void
    {
        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload())
            ->assertSessionHasErrors('avis');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_unpaid_order_does_not_unlock_review(): void
    {
        // Commande créée mais AUCUN paiement : pas un achat réel.
        $order = Order::create([
            'client_id' => $this->client->id,
            'reference' => 'CMD-REV-UNPAID',
            'status' => 'pending',
            'payment_method' => 'MTN Mobile Money',
            'subtotal' => 1500, 'shipping_fee' => 0, 'total' => 1500,
            'shipping_name' => 'A', 'shipping_phone' => '650', 'shipping_address' => 'B',
            'placed_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $this->product->id, 'producer_id' => $this->producer->id,
            'product_name' => $this->product->name, 'unit_price' => 1500, 'unit' => 'kg',
            'quantity' => 1, 'line_total' => 1500,
        ]);

        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload())
            ->assertSessionHasErrors('avis');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_producer_cannot_review_own_product(): void
    {
        $this->actingAs($this->producer)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    /* -------------------- 3. Validation de la note -------------------- */

    public function test_rating_zero_is_rejected(): void
    {
        $this->paidOrder($this->client);

        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload(['note' => 0]))
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_rating_above_five_is_rejected(): void
    {
        $this->paidOrder($this->client);

        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload(['note' => 6]))
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_missing_rating_is_rejected(): void
    {
        $this->paidOrder($this->client);

        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload(['note' => null]))
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('reviews', 0);
    }

    /* ------------------- 4. Validation du commentaire ------------------- */

    public function test_invalid_comment_is_rejected(): void
    {
        $this->paidOrder($this->client);

        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload(['commentaire' => '']))
            ->assertSessionHasErrors('commentaire');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_too_long_comment_is_rejected(): void
    {
        $this->paidOrder($this->client);

        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload([
                'commentaire' => str_repeat('a', 1001),
            ]))
            ->assertSessionHasErrors('commentaire');

        $this->assertDatabaseCount('reviews', 0);
    }

    /* -------------------- 5. Doublon d'avis -------------------- */

    public function test_second_review_for_same_product_is_rejected(): void
    {
        $this->paidOrder($this->client);

        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload())
            ->assertSessionHasNoErrors();

        // Deuxième tentative : refusée proprement (422, pas 500).
        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload(['note' => 1]))
            ->assertSessionHasErrors('avis');

        $this->assertDatabaseCount('reviews', 1);
    }

    /* --------------- 6. Aucun 500 sur les rejets métier --------------- */

    public function test_business_rule_rejection_never_returns_500(): void
    {
        // Régression du bug d'import : ValidationException mal namespacee
        // transformait chaque refus métier en erreur serveur 500.
        $this->actingAs($this->client)
            ->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload())
            ->assertStatus(302)
            ->assertSessionHasErrors('avis');
    }

    /* ---------------- 7. IDOR : modification / suppression ---------------- */

    private function reviewOf(User $author): Review
    {
        return Review::create([
            'product_id' => $this->product->id,
            'client_id' => $author->id,
            'rating' => 4,
            'title' => 'Avis original',
            'comment' => 'Commentaire original suffisamment long.',
            'is_verified_purchase' => true,
            'status' => 'published',
        ]);
    }

    public function test_client_cannot_update_another_clients_review(): void
    {
        $review = $this->reviewOf($this->client);

        $this->actingAs($this->otherClient)
            ->put(route('avis.update', ['review' => $review->id]), [
                'rating' => 1, 'title' => 'Pirate', 'comment' => 'Modification illégale.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 4, 'title' => 'Avis original']);
    }

    public function test_client_cannot_delete_another_clients_review(): void
    {
        $review = $this->reviewOf($this->client);

        $this->actingAs($this->otherClient)
            ->delete(route('avis.destroy', ['review' => $review->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_author_can_update_and_delete_own_review(): void
    {
        $review = $this->reviewOf($this->client);

        $this->actingAs($this->client)
            ->put(route('avis.update', ['review' => $review->id]), [
                'rating' => 3, 'title' => 'Avis révisé', 'comment' => 'Commentaire révisé assez long.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 3, 'title' => 'Avis révisé']);

        $this->actingAs($this->client)
            ->delete(route('avis.destroy', ['review' => $review->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_guest_cannot_post_or_edit_reviews(): void
    {
        $review = $this->reviewOf($this->client);

        $this->post(route('produit.avis', ['slug' => $this->product->slug]), $this->payload())
            ->assertRedirect(route('login'));
        $this->put(route('avis.update', ['review' => $review->id]), [
            'rating' => 1, 'title' => 'x', 'comment' => 'y',
        ])->assertRedirect(route('login'));
    }

    /* ---------------- 8. Affichage des avis réels ---------------- */

    public function test_product_page_displays_real_reviews_and_verified_badge(): void
    {
        $order = $this->paidOrder($this->client);
        Review::create([
            'product_id' => $this->product->id,
            'client_id' => $this->client->id,
            'order_id' => $order->id,
            'rating' => 5,
            'title' => 'Excellent cacao',
            'comment' => 'Produits frais, livraison rapide et vendeur très réactif.',
            'is_verified_purchase' => true,
            'status' => 'published',
        ]);

        $response = $this->get(route('produit', ['slug' => $this->product->slug]));

        $response->assertOk()
            ->assertSee('Excellent cacao')
            ->assertSee('Produits frais, livraison rapide et vendeur très réactif.')
            ->assertSee('Achat vérifié')
            // Note globale réelle : 5,0/5 et non une valeur inventée.
            ->assertSee('5,0');

        // Le nom complet de l'auteur n'est pas exposé tel quel.
        $response->assertDontSee('Awa Njoya');
        $response->assertSee('Awa N.');
    }

    public function test_hidden_reviews_are_not_displayed_nor_counted(): void
    {
        Review::create([
            'product_id' => $this->product->id, 'client_id' => $this->client->id,
            'rating' => 1, 'title' => 'Avis masqué', 'comment' => 'Doit rester invisible.',
            'status' => 'hidden',
        ]);

        $this->get(route('produit', ['slug' => $this->product->slug]))
            ->assertOk()
            ->assertDontSee('Avis masqué')
            ->assertSee('Aucun avis pour ce produit');
    }

    public function test_no_invented_rating_when_product_has_no_review(): void
    {
        $response = $this->get(route('produit', ['slug' => $this->product->slug]));

        // L'ancienne version affichait « 4,8 / 5 » et « 12 évaluations » en dur.
        $response->assertOk()
            ->assertSee('Aucun avis pour ce produit')
            ->assertSee('Pas encore')
            ->assertDontSee('4,8 / 5')
            ->assertDontSee('12 évaluations');
    }

    public function test_catalog_card_does_not_show_fake_rating(): void
    {
        $this->actingAs($this->client)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('4.8 / 5');
    }

    public function test_review_form_is_only_shown_to_clients(): void
    {
        // Client connecté : formulaire présent.
        $this->actingAs($this->client)
            ->get(route('produit', ['slug' => $this->product->slug]))
            ->assertOk()
            ->assertSee('Donner votre avis')
            ->assertSee('review-stars');

        // Producteur : pas de formulaire.
        $this->actingAs($this->producer)
            ->get(route('produit', ['slug' => $this->product->slug]))
            ->assertOk()
            ->assertDontSee('review-stars');
    }

    /* ------------- 9. Statistiques calculées depuis MySQL ------------- */

    public function test_product_statistics_come_from_database(): void
    {
        $stats = app(\App\Services\ReviewService::class)->productStats($this->product);

        $this->assertNull($stats['average']);
        $this->assertSame(0, $stats['count']);

        Review::create(['product_id' => $this->product->id, 'client_id' => $this->client->id, 'rating' => 5, 'status' => 'published']);
        Review::create(['product_id' => $this->product->id, 'client_id' => $this->otherClient->id, 'rating' => 3, 'status' => 'published']);

        $stats = app(\App\Services\ReviewService::class)->productStats($this->product);

        $this->assertSame(4.0, $stats['average']);
        $this->assertSame(2, $stats['count']);
        $this->assertSame(1, $stats['distribution'][5]);
        $this->assertSame(1, $stats['distribution'][3]);
    }
}
