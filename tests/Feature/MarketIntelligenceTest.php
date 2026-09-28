<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Bloc C (2/2) — Marché des prix + Recommandations IA (OpenRouter).
 * L'API OpenRouter n'est JAMAIS appelée réellement : Http::fake() partout.
 */
class MarketIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private array $products = [];

    protected function setUp(): void
    {
        parent::setUp();

        $producer = User::factory()->create(['role' => 'producer', 'name' => 'Ferme Test', 'region' => 'Centre']);
        $category = Category::create(['name' => 'Céréales', 'slug' => 'cereales']);

        $this->client = User::factory()->create(['role' => 'client', 'region' => 'Centre']);

        // 3 offres actives sur le même produit (Sorgho) + 1 produit hors stock.
        foreach ([1500, 2000, 2500] as $i => $price) {
            $this->products[] = Product::create([
                'producer_id' => $producer->id,
                'category_id' => $category->id,
                'name' => 'Sorgho',
                'slug' => 'sorgho-'.$i,
                'price' => $price,
                'unit' => 'kg',
                'stock_quantity' => 10,
                'is_available' => true,
                'status' => 'published',
            ]);
        }

        Product::create([
            'producer_id' => $producer->id,
            'category_id' => $category->id,
            'name' => 'Hors stock',
            'slug' => 'hors-stock',
            'price' => 999,
            'unit' => 'kg',
            'stock_quantity' => 0,
            'is_available' => true,
            'status' => 'published',
        ]);
    }

    /* --------------------------- Marché des prix --------------------------- */

    public function test_market_price_stats_are_correctly_computed(): void
    {
        $resp = $this->get(route('marche.prix'));
        $resp->assertOk();

        $service = new \App\Services\MarketPriceService();
        $stats = $service->stats();

        $sorgho = $stats->firstWhere('name', 'Sorgho');
        $this->assertNotNull($sorgho);
        $this->assertSame(3, $sorgho['offers']);
        $this->assertEquals(2000.0, $sorgho['avg']);
        $this->assertEquals(1500.0, $sorgho['min']);
        $this->assertEquals(2500.0, $sorgho['max']);

        // Le produit hors stock ne doit jamais apparaître.
        $this->assertNull($stats->firstWhere('name', 'Hors stock'));
    }

    /* ---------------------- Recommandations IA (OpenRouter) ---------------------- */

    /** Simule une réponse OpenRouter réussie (format OpenAI chat/completions). */
    private function fakeAiOk(array $idsWithReason): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);

        $payload = ['recommendations' => $idsWithReason];

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => json_encode($payload)]],
                ],
            ]),
        ]);
    }

    public function test_valid_ai_response_returns_existing_products_only(): void
    {
        $this->fakeAiOk([
            ['id' => $this->products[0]->id, 'reason' => 'Bon prix sur le sorgho.'],
            ['id' => 999, 'reason' => 'ID halluciné — doit être ignoré.'],
            ['id' => $this->products[2]->id, 'reason' => 'Offre fiable.'],
        ]);

        $resp = $this->actingAs($this->client)->getJson(route('recommandations.ia'));
        $resp->assertOk()->assertJsonCount(2, 'recommendations');

        $ids = collect($resp->json('recommendations'))->pluck('id')->all();
        $this->assertContains($this->products[0]->id, $ids);
        $this->assertContains($this->products[2]->id, $ids);
        $this->assertNotContains(999, $ids);
    }

    public function test_ai_response_is_tagged_with_openrouter_source(): void
    {
        $this->fakeAiOk([['id' => $this->products[0]->id, 'reason' => 'Test source.']]);

        $resp = $this->actingAs($this->client)->getJson(route('recommandations.ia'));
        $resp->assertOk();

        $this->assertSame('openrouter', $resp->json('recommendations.0.source'));
    }

    public function test_ai_key_is_sent_in_authorization_header_never_in_body(): void
    {
        $this->fakeAiOk([['id' => $this->products[0]->id, 'reason' => 'Test sécurité.']]);

        $this->actingAs($this->client)->getJson(route('recommandations.ia'))->assertOk();

        Http::assertSent(function ($request) {
            $body = (string) $request->body();

            // La clé ne doit apparaître NI dans le corps NI dans l'URL.
            $this->assertStringNotContainsString('sk-or-test-key', $body);
            $this->assertStringNotContainsString('sk-or-test-key', $request->url());

            // Elle doit être présente dans l'en-tête Bearer.
            $this->assertSame('Bearer sk-or-test-key', $request->header('Authorization')[0] ?? null);

            return true;
        });
    }

    public function test_missing_api_key_falls_back_to_local_recommendations(): void
    {
        config(['services.openrouter.key' => null]);
        Http::fake();

        $resp = $this->actingAs($this->client)->getJson(route('recommandations.ia'));

        $resp->assertOk();
        $this->assertNotEmpty($resp->json('recommendations'));
        foreach ($resp->json('recommendations') as $item) {
            $this->assertSame('local', $item['source']);
        }

        // Aucune requête sortante ne doit partir sans clé.
        Http::assertNothingSent();
    }

    public function test_invalid_api_key_falls_back_to_local_recommendations(): void
    {
        config(['services.openrouter.key' => 'sk-or-cle-invalide']);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'error' => ['code' => 401, 'message' => 'No auth credentials found'],
            ], 401),
        ]);

        $resp = $this->actingAs($this->client)->getJson(route('recommandations.ia'));

        // La marketplace ne doit jamais casser : repli local.
        $resp->assertOk();
        $this->assertNotEmpty($resp->json('recommendations'));
        foreach ($resp->json('recommendations') as $item) {
            $this->assertSame('local', $item['source']);
        }
    }

    public function test_rate_limit_falls_back_to_local_recommendations(): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);

        Http::fake(['openrouter.ai/*' => Http::response([
            'error' => ['code' => 429, 'message' => 'Rate limit exceeded'],
        ], 429)]);

        $resp = $this->actingAs($this->client)->getJson(route('recommandations.ia'));
        $resp->assertOk();
        $this->assertSame('local', $resp->json('recommendations.0.source'));
    }

    public function test_empty_ai_answer_falls_back_to_local_recommendations(): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);

        Http::fake([
            'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => '']]]]),
        ]);

        $resp = $this->actingAs($this->client)->getJson(route('recommandations.ia'));
        $resp->assertOk();
        $this->assertSame('local', $resp->json('recommendations.0.source'));
    }

    public function test_non_json_ai_answer_falls_back_to_local_recommendations(): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'Voici du texte, pas du JSON.']]],
            ]),
        ]);

        $resp = $this->actingAs($this->client)->getJson(route('recommandations.ia'));
        $resp->assertOk();
        $this->assertSame('local', $resp->json('recommendations.0.source'));
    }

    public function test_unavailable_model_falls_back_to_local_recommendations(): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'error' => ['code' => 404, 'message' => 'No endpoints found for model'],
            ], 404),
        ]);

        $resp = $this->actingAs($this->client)->getJson(route('recommandations.ia'));
        $resp->assertOk();
        $this->assertSame('local', $resp->json('recommendations.0.source'));
    }

    public function test_ai_failure_falls_back_to_local_recommendations(): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);
        Http::fake(['openrouter.ai/*' => Http::response('', 500)]);

        $resp = $this->actingAs($this->client)->getJson(route('recommandations.ia'));

        // La page ne doit PAS planter : repli local sur offres réelles.
        $resp->assertOk();
        $body = $resp->json('recommendations');
        $this->assertNotEmpty($body);
        foreach ($body as $item) {
            $this->assertEquals('local', $item['source']);
        }
    }

    public function test_recommendations_are_cached_between_two_calls(): void
    {
        $this->fakeAiOk([['id' => $this->products[0]->id, 'reason' => 'Test cache.']]);

        $this->actingAs($this->client)->getJson(route('recommandations.ia'))->assertOk();
        $this->actingAs($this->client)->getJson(route('recommandations.ia'))->assertOk();

        // Un seul appel HTTP à OpenRouter malgré deux requêtes (cache utilisé).
        Http::assertSentCount(1);
    }

    public function test_guest_cannot_access_recommendations(): void
    {
        // JSON attendu : l'invité est rejeté (401 par défaut sur getJson).
        $this->getJson(route('recommandations.ia'))->assertStatus(401);
    }

    public function test_bubble_not_rendered_for_guest_or_producer(): void
    {
        // Invité : pas de bulle.
        $this->get(route('marketplace'))->assertOk()->assertDontSee('ai-bubble-root');

        // Producteur : pas de bulle non plus.
        $producer = User::factory()->create(['role' => 'producer']);
        $this->actingAs($producer)->get(route('marketplace'))->assertOk()->assertDontSee('ai-bubble-root');
    }
}
