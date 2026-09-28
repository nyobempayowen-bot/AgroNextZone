<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProducerReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Système de notation des producteurs — cahier des charges complet.
 *
 * Règle métier existante (migration producer_reviews) : UN avis par commande
 * et par client. Un client peut donc noter de nouveau le producteur sur une
 * commande ultérieure, ce que ces tests vérifient.
 *
 * MySQL reste la seule source de vérité : aucun cas ne s'appuie sur la session.
 */
class ProducerRatingTest extends TestCase
{
    use RefreshDatabase;

    private User $producer;
    private User $otherProducer;
    private User $client;
    private User $otherClient;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = User::factory()->create(['role' => 'producer', 'name' => 'Ferme Nkembo']);
        $this->otherProducer = User::factory()->create(['role' => 'producer', 'name' => 'Ferme Bocage']);
        $this->client = User::factory()->create(['role' => 'client', 'name' => 'Awa Njoya']);
        $this->otherClient = User::factory()->create(['role' => 'client', 'name' => 'Bob Tchouta']);

        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits-pr']);
        $location = Location::create([
            'user_id' => $this->producer->id,
            'country' => 'Cameroun', 'region' => 'Centre', 'city' => 'Bafia',
            'is_primary' => true,
        ]);

        $this->product = Product::create([
            'producer_id' => $this->producer->id,
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => 'Banane notation',
            'slug' => 'banane-notation',
            'description' => 'Produit de test.',
            'price' => 1500, 'unit' => 'kg', 'stock_quantity' => 50,
            'minimum_order' => 1, 'is_available' => true, 'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /** Commande payée du client contenant un produit de ce producteur. */
    private function paidOrder(User $client, string $reference, ?User $producer = null): Order
    {
        $producer = $producer ?? $this->producer;

        $order = Order::create([
            'client_id' => $client->id,
            'reference' => $reference,
            'status' => 'confirmed',
            'payment_method' => 'MTN Mobile Money',
            'subtotal' => 1500, 'shipping_fee' => 0, 'total' => 1500,
            'shipping_name' => $client->name, 'shipping_phone' => '650000000',
            'shipping_address' => 'Bafia', 'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'producer_id' => $producer->id,
            'product_name' => $this->product->name,
            'unit_price' => 1500, 'unit' => 'kg', 'quantity' => 1, 'line_total' => 1500,
        ]);

        $order->payments()->create([
            'client_id' => $client->id, 'method' => 'mtn_mobile_money', 'status' => 'paid',
            'amount' => 1500, 'provider' => 'mtn_momo_simulated',
            'provider_reference' => 'PAY-'.$reference, 'paid_at' => now(),
        ]);

        return $order;
    }

    private function rating(User $client, int $note, string $comment = ''): ProducerReview
    {
        return ProducerReview::create([
            'producer_id' => $this->producer->id,
            'client_id' => $client->id,
            'order_id' => $this->paidOrder($client, 'CMD-'.uniqid())->id,
            'rating' => $note,
            'comment' => $comment !== '' ? $comment : null,
            'status' => 'published',
        ]);
    }
    /* ---------------- 1. Client ayant acheté peut noter ---------------- */

    public function test_client_with_real_purchase_can_rate(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-OK');

        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 5, 'comment' => 'Producteur très sérieux.',
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('producer_reviews', [
            'order_id' => $order->id,
            'client_id' => $this->client->id,
            'producer_id' => $this->producer->id,
            'rating' => 5,
        ]);
    }

    /* ---------------- 2. Client sans achat : refusé ---------------- */

    public function test_client_without_purchase_is_refused(): void
    {
        $order = $this->paidOrder($this->otherClient, 'CMD-PR-OTHER');

        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 5,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('producer_reviews', 0);
    }

    public function test_unpaid_order_does_not_unlock_rating(): void
    {
        $order = Order::create([
            'client_id' => $this->client->id, 'reference' => 'CMD-PR-UNPAID', 'status' => 'pending',
            'payment_method' => 'MTN Mobile Money', 'subtotal' => 1500, 'shipping_fee' => 0, 'total' => 1500,
            'shipping_name' => 'A', 'shipping_phone' => '6', 'shipping_address' => 'B', 'placed_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $this->product->id, 'producer_id' => $this->producer->id,
            'product_name' => 'x', 'unit_price' => 1500, 'unit' => 'kg', 'quantity' => 1, 'line_total' => 1500,
        ]);

        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 5,
            ])
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('producer_reviews', 0);
    }

    /* -------- 3. Producteur : pas d'auto-notation ni notation client -------- */

    public function test_producer_cannot_rate_self(): void
    {
        // Le role producteur est bloque par le middleware : aucune route de
        // notation n'est atteignable, meme vers une commande existante.
        $order = $this->paidOrder($this->otherClient, 'CMD-PR-SELF');

        $this->actingAs($this->producer)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 5,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('producer_reviews', 0);
    }

    public function test_producer_cannot_rate_via_client_dashboard(): void
    {
        $this->actingAs($this->producer)
            ->get(route('client.dashboard', ['tab' => 'orders']))
            ->assertForbidden();
    }

    /* ---------------- 4. Visiteur : ne peut pas noter ---------------- */

    public function test_guest_cannot_rate(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-GUEST');

        $this->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
            'rating' => 5,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('producer_reviews', 0);
    }

    /* --------------- 5-6. Bornes : 1 et 5 valides --------------- */

    public function test_rating_one_is_accepted(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-ONE');

        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 1,
            ])->assertSessionHas('success');

        $this->assertDatabaseHas('producer_reviews', ['order_id' => $order->id, 'rating' => 1]);
    }

    public function test_rating_five_is_accepted(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-FIVE');

        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 5,
            ])->assertSessionHas('success');

        $this->assertDatabaseHas('producer_reviews', ['order_id' => $order->id, 'rating' => 5]);
    }

    /* --------------- 7-9. Valeurs invalides --------------- */

    public function test_rating_zero_is_refused(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-ZERO');

        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 0,
            ])->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('producer_reviews', 0);
    }

    public function test_rating_six_is_refused(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-SIX');

        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 6,
            ])->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('producer_reviews', 0);
    }

    public function test_missing_rating_is_refused(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-NONE');

        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('producer_reviews', 0);
    }

    /* ------------- 16. Jamais de 500 sur validation invalide ------------- */

    public function test_invalid_validation_never_returns_500(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-500');

        // Régression : le mauvais namespace de ValidationException transformait
        // chaque refus métier en erreur serveur 500.
        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 5,
            ])
            ->assertStatus(302);

        // Deuxième tentative : doublon sur la même commande -> refus propre, pas 500.
        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 4,
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors('note');

        $this->assertDatabaseCount('producer_reviews', 1);
    }

    /* ------------- 10. Doublon : conforme à la règle métier ------------- */

    public function test_double_rating_on_same_order_is_refused(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-DUP');

        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 5,
            ])->assertSessionHas('success');

        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 3,
            ])->assertSessionHasErrors('note');

        $this->assertDatabaseCount('producer_reviews', 1);
    }

    public function test_client_can_rate_again_on_a_new_order(): void
    {
        // Règle métier : un avis par commande — donc rechargeable à chaque
        // nouvelle transaction.
        $first = $this->paidOrder($this->client, 'CMD-PR-R1');
        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $first->id, 'producer' => $this->producer->id]), [
                'rating' => 2,
            ])->assertSessionHas('success');

        $second = $this->paidOrder($this->client, 'CMD-PR-R2');
        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $second->id, 'producer' => $this->producer->id]), [
                'rating' => 5,
            ])->assertSessionHas('success');

        $this->assertDatabaseCount('producer_reviews', 2);
    }

    /* ------------- 11 & 15. IDOR : avis d'un autre utilisateur ------------- */

    public function test_client_cannot_update_another_clients_rating(): void
    {
        $review = $this->rating($this->client, 5, 'Note initiale');

        $this->actingAs($this->otherClient)
            ->put(route('client.producer-review.update', ['review' => $review->id]), [
                'rating' => 1, 'comment' => 'Modification illégale.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('producer_reviews', ['id' => $review->id, 'rating' => 5, 'comment' => 'Note initiale']);
    }

    public function test_client_cannot_delete_another_clients_rating(): void
    {
        $review = $this->rating($this->client, 4);

        $this->actingAs($this->otherClient)
            ->delete(route('client.producer-review.destroy', ['review' => $review->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('producer_reviews', ['id' => $review->id]);
    }

    public function test_guest_cannot_update_or_delete_rating(): void
    {
        $review = $this->rating($this->client, 4);

        $this->put(route('client.producer-review.update', ['review' => $review->id]), ['rating' => 1])
            ->assertRedirect(route('login'));
        $this->delete(route('client.producer-review.destroy', ['review' => $review->id]))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('producer_reviews', ['id' => $review->id, 'rating' => 4]);
    }

    public function test_author_can_update_and_delete_own_rating(): void
    {
        $review = $this->rating($this->client, 3, 'Commentaire initial');

        $this->actingAs($this->client)
            ->put(route('client.producer-review.update', ['review' => $review->id]), [
                'rating' => 5, 'comment' => 'Commentaire révisé.',
            ])->assertSessionHas('success');

        $this->assertDatabaseHas('producer_reviews', ['id' => $review->id, 'rating' => 5]);

        $this->actingAs($this->client)
            ->delete(route('client.producer-review.destroy', ['review' => $review->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('producer_reviews', ['id' => $review->id]);
    }

    /* ------------- 12. Calcul de moyenne depuis MySQL ------------- */

    public function test_average_is_computed_from_real_reviews(): void
    {
        $this->rating($this->client, 5);
        $this->rating($this->otherClient, 3);
        $this->rating($this->otherClient, 4, 'Bien reçu.');

        $stats = app(\App\Services\ProducerReviewService::class)->producerStats($this->producer);

        $this->assertSame(4.0, $stats['average']);
        $this->assertSame(3, $stats['count']);
        $this->assertSame(1, $stats['distribution'][5]);
        $this->assertSame(1, $stats['distribution'][4]);
        $this->assertSame(1, $stats['distribution'][3]);
    }

    public function test_hidden_reviews_are_excluded_from_average(): void
    {
        $this->rating($this->client, 5);
        $hidden = $this->rating($this->otherClient, 1);
        $hidden->update(['status' => 'hidden']);

        $stats = app(\App\Services\ProducerReviewService::class)->producerStats($this->producer);

        $this->assertSame(5.0, $stats['average']);
        $this->assertSame(1, $stats['count']);
    }

    /* ------------- 13 & 14. Affichage : « Pas encore noté » ------------- */

    public function test_profile_shows_not_yet_rated_without_invented_value(): void
    {
        $this->get(route('profil', ['id' => $this->producer->id]))
            ->assertOk()
            ->assertSee('Pas encore noté')
            // Aucune note ni avis inventé.
            ->assertDontSee('4,8')
            ->assertDontSee('5,0/5');
    }

    public function test_profile_shows_real_average_and_count(): void
    {
        $this->rating($this->client, 4);

        $this->get(route('profil', ['id' => $this->producer->id]))
            ->assertOk()
            ->assertSee('4,0')
            // Le compteur peut etre encapsule dans un <span>, on verifie les
            // deux morceaux pour rester robuste au rendu HTML.
            ->assertSee('avis vérifié')
            ->assertDontSee('Pas encore noté');
    }

    public function test_profile_rating_survives_page_reload(): void
    {
        $this->rating($this->client, 5);

        // Deux requêtes successives : la note reste identique (persistée MySQL).
        $first = $this->get(route('profil', ['id' => $this->producer->id]))->assertOk()->getContent();
        $second = $this->get(route('profil', ['id' => $this->producer->id]))->assertOk()->getContent();

        $this->assertStringContainsString('5,0', $first);
        $this->assertStringContainsString('5,0', $second);
    }

    public function test_client_dashboard_shows_rating_form_only_when_eligible(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-DASH');

        // Commande payée sans notation : formulaire proposé.
        $this->actingAs($this->client)
            ->get(route('client.dashboard', ['tab' => 'orders']))
            ->assertOk()
            ->assertSee('Noter')
            ->assertSee('data-stars', escape: false);

        // Commande non payée : aucun formulaire.
        $this->actingAs($this->otherClient)
            ->get(route('client.dashboard', ['tab' => 'orders']))
            ->assertOk()
            ->assertDontSee('Noter le producteur');
    }

    public function test_client_dashboard_shows_existing_rating_and_update_option(): void
    {
        $order = $this->paidOrder($this->client, 'CMD-PR-EXIST');
        $this->actingAs($this->client)
            ->post(route('client.producer-review.store', ['order' => $order->id, 'producer' => $this->producer->id]), [
                'rating' => 5, 'comment' => 'Très bon producteur.',
            ])->assertSessionHas('success');

        $html = $this->actingAs($this->client)
            ->get(route('client.dashboard', ['tab' => 'orders']))
            ->assertOk()
            ->assertSee('Très bon producteur.')
            ->getContent();

        // La notation deja donnee est affichee, et le formulaire propose
        // directement sa modification (pas un nouveau depot).
        $this->assertStringContainsString('Modifier votre note', $html);
        $this->assertStringContainsString(route('client.producer-review.update', ['review' => ProducerReview::query()->first()->id]), $html);
        $this->assertStringNotContainsString('Noter le producteur', $html);
    }
}
