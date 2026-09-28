<?php

namespace Tests\Feature;

use App\Models\LocalDish;
use App\Models\LocalDishIngredient;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Bloc D — Assistant repas conversationnel.
 * L'API OpenRouter n'est JAMAIS appelée réellement : Http::fake() partout.
 */
class DishAssistantTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $producerTop;

    private User $producerLow;

    private Product $productManiocTop;

    private Product $productManiocLow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['role' => 'client']);

        // Deux producteurs : le mieux noté doit passer en premier.
        $this->producerTop = User::factory()->create(['role' => 'producer', 'name' => 'Ferme A']);
        $this->producerLow = User::factory()->create(['role' => 'producer', 'name' => 'Ferme B']);

        $cat = \App\Models\Category::create(['name' => 'Tubercules', 'slug' => 'tubercules']);

        $this->productManiocTop = Product::create([
            'producer_id' => $this->producerTop->id,
            'category_id' => $cat->id,
            'name' => 'Manioc frais',
            'slug' => 'manioc-frais',
            'price' => 800,
            'unit' => 'kg',
            'stock_quantity' => 50,
            'is_available' => true,
            'status' => 'published',
        ]);

        $this->productManiocLow = Product::create([
            'producer_id' => $this->producerLow->id,
            'category_id' => $cat->id,
            'name' => 'Manioc fermenté',
            'slug' => 'manioc-fermente',
            'price' => 900,
            'unit' => 'kg',
            'stock_quantity' => 50,
            'is_available' => true,
            'status' => 'published',
        ]);

        // Notes : Ferme A (5/5 sur son produit), Ferme B (2/5).
        Review::create(['product_id' => $this->productManiocTop->id, 'client_id' => $this->client->id, 'rating' => 5, 'status' => 'published']);
        Review::create(['product_id' => $this->productManiocLow->id, 'client_id' => $this->client->id, 'rating' => 2, 'status' => 'published']);

        // Base de connaissance : le Bâton de manioc (plat connu).
        $dish = LocalDish::create([
            'name' => 'Bâton de manioc',
            'region' => 'Sud',
            'description' => 'Manioc fermenté cuit en papillote.',
        ]);
        LocalDishIngredient::create(['local_dish_id' => $dish->id, 'ingredient_name' => 'Manioc', 'is_optional' => false]);
        LocalDishIngredient::create(['local_dish_id' => $dish->id, 'ingredient_name' => 'Feuilles de bananier', 'is_optional' => false]);
    }

    /** Simule une réponse OpenRouter réussie (format OpenAI chat/completions). */
    private function fakeAi(string $reply, array $ingredients): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => [
                        'role' => 'assistant',
                        'content' => json_encode(['reply' => $reply, 'ingredients' => $ingredients]),
                    ]],
                ],
            ]),
        ]);
    }

    /* ------------------------- Chat assistant repas ------------------------- */

    public function test_known_dish_returns_reply_and_real_products_sorted_by_score(): void
    {
        $this->fakeAi(
            'Le bâton de manioc se prépare avec du manioc. Voici ce que nous avons.',
            ['Manioc']
        );

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Je veux préparer du Bâton de manioc',
        ]);

        $resp->assertOk();
        $data = $resp->json();

        $this->assertTrue($data['known_dish']);
        $this->assertFalse($data['api_error']);
        $this->assertStringContainsString('bâton de manioc', mb_strtolower($data['reply']));

        // 2 vrais produits, le mieux noté (Ferme A, score 100) en premier.
        $this->assertCount(2, $data['products']);
        $this->assertEquals($this->productManiocTop->id, $data['products'][0]['id']);
        $this->assertEquals(100, $data['products'][0]['score']);
        $this->assertEquals($this->productManiocLow->id, $data['products'][1]['id']);
        $this->assertEquals(40, $data['products'][1]['score']);
    }

    public function test_unknown_dish_gets_honest_answer_without_products_hallucinated(): void
    {
        // Aucun plat connu ne correspond : la base transmise est vide.
        $this->fakeAi(
            "Je ne connais pas encore ce plat, mais précisez-moi vos ingrédients et je vous propose des produits.",
            []
        );

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Comment préparer le Mbongo Tchobi ?',
        ]);

        $resp->assertOk();
        $data = $resp->json();

        $this->assertFalse($data['known_dish']);
        $this->assertStringContainsString('ne connais pas encore', $data['reply']);
        $this->assertSame([], $data['products']);
    }

    public function test_no_matching_real_product_is_explicitly_reported(): void
    {
        // L'IA demande un ingrédient dont aucun produit réel n'existe.
        $this->fakeAi('Il vous faut du gombo.', ['Gombo frais']);

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Je veux faire de la sauce gombo',
        ]);

        $resp->assertOk();
        $this->assertSame([], $resp->json('products'));
    }

    public function test_ai_outage_returns_clean_fallback_without_crash(): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);
        Http::fake(['openrouter.ai/*' => Http::response('', 503)]);

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Je veux faire du Ndolé',
        ]);

        $resp->assertOk();
        $data = $resp->json();

        $this->assertTrue($data['api_error']);
        $this->assertStringContainsString('temporairement indisponible', $data['reply']);
        $this->assertSame([], $data['products']);
    }

    public function test_conversation_history_is_kept_in_session(): void
    {
        $this->fakeAi('Réponse 1.', ['Manioc']);
        $this->actingAs($this->client)->postJson(route('assistant-repas.send'), ['message' => 'Bâton de manioc ?'])->assertOk();

        $this->fakeAi('Et à la place du manioc, essayez le plantain.', ['Plantain']);
        $this->actingAs($this->client)->postJson(route('assistant-repas.send'), ['message' => 'Et s\'il n\'y a pas de manioc ?'])->assertOk();

        $history = $this->actingAs($this->client)->get(route('assistant-repas'))->viewData('history');

        // 2 échanges = 4 tours (client + assistant à chaque fois).
        $this->assertCount(4, $history);
    }

    public function test_guest_and_producer_cannot_use_assistant(): void
    {
        $this->get(route('assistant-repas'))->assertRedirect();

        $producer = User::factory()->create(['role' => 'producer']);
        $this->actingAs($producer)->get(route('assistant-repas'))->assertForbidden();
    }

    public function test_ai_key_is_sent_in_authorization_header_never_in_body(): void
    {
        $this->fakeAi('Voici les ingrédients.', ['Manioc']);

        $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Bâton de manioc ?',
        ])->assertOk();

        Http::assertSent(function ($request) {
            $this->assertStringNotContainsString('sk-or-test-key', (string) $request->body());
            $this->assertStringNotContainsString('sk-or-test-key', $request->url());
            $this->assertSame('Bearer sk-or-test-key', $request->header('Authorization')[0] ?? null);

            return true;
        });
    }

    public function test_missing_api_key_returns_clean_fallback_without_crash(): void
    {
        config(['services.openrouter.key' => null]);
        Http::fake();

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Je veux faire du Ndolé',
        ]);

        $resp->assertOk();
        $this->assertTrue($resp->json('api_error'));
        $this->assertStringContainsString('temporairement indisponible', $resp->json('reply'));
    }

    /* --------------------------- CRUD admin plats --------------------------- */

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /* ------------------- Saisie assistée par IA (2-B) ------------------- */

    public function test_ai_extract_returns_proposal_without_writing_to_database(): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'name' => 'Ndolé',
                        'region' => 'Littoral',
                        'description' => 'Plat national.',
                        'ingredients' => [
                            ['name' => 'Feuilles de ndolé', 'optional' => false],
                            ['name' => 'Piment', 'optional' => true],
                        ],
                    ])]],
                ],
            ]),
        ]);

        $resp = $this->actingAs($this->admin())
            ->postJson(route('admin.dishes.extract'), [
                'texte' => 'Le Ndolé se prépare avec des feuilles de ndolé, de la pâte d\'arachide et du poisson fumé.',
            ]);

        $resp->assertOk();
        $data = $resp->json();
        $this->assertSame('Ndolé', $data['name']);
        $this->assertCount(2, $data['ingredients']);

        // Rien ne doit être enregistré : la validation humaine est obligatoire.
        // (Le seeder peut avoir peuplé la base : on vérifie que le plat extrait n'y est pas
        // et qu'aucune ligne n'a été créée au-delà des données de seed.)
        $seededCount = LocalDish::count();
        $this->assertDatabaseMissing('local_dishes', ['name' => 'Ndolé']);
        $this->assertSame($seededCount, LocalDish::count());
    }

    public function test_ai_extract_outage_returns_clean_503_without_crash(): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);
        Http::fake(['openrouter.ai/*' => Http::response('', 503)]);

        $seededCount = LocalDish::count();
        $this->actingAs($this->admin())
            ->postJson(route('admin.dishes.extract'), ['texte' => 'Le Koki se prépare avec des haricots coco.'])
            ->assertStatus(503);

        $this->assertSame($seededCount, LocalDish::count());
        $this->assertDatabaseMissing('local_dishes', ['name' => 'Koki']);
    }

    public function test_non_admin_cannot_use_ai_extract(): void
    {
        $this->actingAs($this->client)
            ->postJson(route('admin.dishes.extract'), ['texte' => 'Un plat avec du manioc et du plantain.'])
            ->assertForbidden();
    }

    public function test_admin_can_manage_dishes(): void
    {
        $admin = $this->admin();

        // Création.
        $resp = $this->actingAs($admin)->post(route('admin.dishes.store'), [
            'name' => 'Koki',
            'region' => 'Ouest',
            'description' => 'Haricots coco cuits en papillote.',
            'ingredients' => "Haricots coco\nHuile de palme | optionnel",
        ]);
        $resp->assertRedirect(route('admin.dishes'));

        $dish = LocalDish::where('name', 'Koki')->first();
        $this->assertNotNull($dish);
        $this->assertSame('Ouest', $dish->region);
        $this->assertSame(2, $dish->ingredients()->count());
        $this->assertTrue($dish->ingredients()->where('ingredient_name', 'Huile de palme')->first()->is_optional);

        // Modification.
        $this->actingAs($admin)->put(route('admin.dishes.update', $dish), [
            'name' => 'Koki maïs',
            'region' => 'Ouest',
            'description' => null,
            'ingredients' => "Maïs",
        ])->assertRedirect(route('admin.dishes'));

        $dish->refresh();
        $this->assertSame('Koki maïs', $dish->name);
        $this->assertSame(1, $dish->ingredients()->count());
        $this->assertSame('Maïs', $dish->ingredients()->first()->ingredient_name);

        // Suppression.
        $this->actingAs($admin)->delete(route('admin.dishes.destroy', $dish))->assertRedirect(route('admin.dishes'));
        $this->assertDatabaseMissing('local_dishes', ['id' => $dish->id]);
        $this->assertDatabaseMissing('local_dish_ingredients', ['local_dish_id' => $dish->id]);
    }

    public function test_non_admin_cannot_manage_dishes(): void
    {
        $client = $this->client;

        $this->actingAs($client)->get(route('admin.dishes'))->assertForbidden();
        $this->actingAs($client)->post(route('admin.dishes.store'), ['name' => 'X'])->assertForbidden();
    }

    public function test_dishes_admin_page_lists_seeded_dishes(): void
    {
        $resp = $this->actingAs($this->admin())->get(route('admin.dishes'));
        $resp->assertOk()->assertSee('Bâton de manioc');
    }
}
