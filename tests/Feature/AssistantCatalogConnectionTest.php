<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LocalDish;
use App\Models\LocalDishIngredient;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Services\CatalogSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Assistant connecté EN TEMPS RÉEL au catalogue AgroNextZone.
 *
 * AgroNextZone = source de vérité commerciale. L'IA sert à comprendre
 * et formuler, jamais à décider de ce qui est en vente.
 */
class AssistantCatalogConnectionTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $producer;
    private Location $location;
    private Category $category;
    private Product $ndole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['role' => 'client']);
        $this->producer = User::factory()->create(['role' => 'producer', 'name' => 'Ferme de Bafia']);

        $this->location = Location::create([
            'user_id' => $this->producer->id,
            'country' => 'Cameroun',
            'region' => 'Ouest',
            'city' => 'Bafia',
            'is_primary' => true,
        ]);

        $this->category = Category::create(['name' => 'Légumes', 'slug' => 'legumes-ai']);

        $this->ndole = Product::create([
            'producer_id' => $this->producer->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'name' => 'Feuilles de Ndolé fraîches',
            'slug' => 'feuilles-ndole',
            'description' => 'Feuilles amèresanga kenyang Incorrect',
            'price' => 450,
            'unit' => 'botte',
            'stock_quantity' => 30,
            'minimum_order' => 1,
            'is_available' => true,
            'status' => 'published',
        ]);

        $dish = LocalDish::create([
            'name' => 'Ndolé',
            'region' => 'Littoral',
            'description' => 'Salade de feuilles amères.',
        ]);
        LocalDishIngredient::create(['local_dish_id' => $dish->id, 'ingredient_name' => 'Feuilles de ndolé', 'is_optional' => false]);
    }

    /** Simule une réponse OpenRouter (jamais un vrai appel réseau). */
    private function fakeAi(string $reply, array $ingredients = []): void
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

    private function catalog(): CatalogSearchService
    {
        return app(CatalogSearchService::class);
    }

    /* ---------- 1-2 : question sur un produit existant ---------- */

    /** 1. Question sur un produit existant → recherche MySQL effectuée. */
    public function test_existing_product_question_is_answered_from_database(): void
    {
        $this->fakeAi('Oui, j\'ai trouvé des feuilles de Ndolé.');

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Avez-vous des feuilles de Ndolé ?',
        ]);

        $resp->assertOk();

        // La base a bien été interrogée AVANT de répondre.
        $data = $resp->json();
        $this->assertTrue($data['catalogue_checked']);
        $this->assertNotEmpty($data['catalogue']);
        $this->assertSame($this->ndole->id, $data['catalogue'][0]['id']);
    }

    /** 2. Produit disponible → produit réel retourné. */
    public function test_available_real_product_is_returned(): void
    {
        $products = $this->catalog()->searchProducts(['ndole']);

        $this->assertCount(1, $products);
        $this->assertSame('Feuilles de Ndolé fraîches', $products->first()['name']);
        $this->assertTrue($products->first()['purchasable']);
    }

    /** 3. Produit inexistant → aucune invention. */
    public function test_unknown_product_invents_nothing(): void
    {
        $this->fakeAi('Je ne trouve rien pour le pangolin.');

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Avez-vous de la viande de pangolin ?',
        ]);

        $resp->assertOk();

        // Aucun produit inventé.
        $this->assertSame([], $resp->json('catalogue'));
        $this->assertSame([], $resp->json('products'));
    }

    /** 4. Produit indisponible → correctement signalé. */
    public function test_unavailable_product_is_flagged(): void
    {
        $this->ndole->update(['is_available' => false]);
        $this->ndole->refresh();

        $products = $this->catalog()->searchProducts(['ndole']);

        $this->assertCount(1, $products);
        $this->assertFalse($products->first()['purchasable']);
        $this->assertSame('Indisponible', $products->first()['disponibilite']);
    }

    /** 4b. Rupture de stock signalée honnêtement. */
    public function test_out_of_stock_is_flagged(): void
    {
        $this->ndole->update(['stock_quantity' => 0]);
        $this->ndole->refresh();

        $products = $this->catalog()->searchProducts(['ndole']);

        $this->assertFalse($products->first()['purchasable']);
        $this->assertSame('Rupture de stock', $products->first()['disponibilite']);
    }

    /** 5. Stock réel affiché depuis MySQL. */
    public function test_real_stock_is_reported(): void
    {
        // Stock faible : l'étiquette de disponibilité doit le refléter.
        $this->ndole->update(['stock_quantity' => 3]);
        $this->ndole->refresh();

        $products = $this->catalog()->searchProducts(['ndole']);

        $this->assertSame(3, $products->first()['stock']);
        $this->assertStringContainsString('3', $products->first()['disponibilite']);

        // Stock confortable : disponible, et le stock exact reste exposé.
        $this->ndole->update(['stock_quantity' => 40]);
        $this->ndole->refresh();

        $products = $this->catalog()->searchProducts(['ndole']);
        $this->assertSame(40, $products->first()['stock']);
        $this->assertSame('Disponible', $products->first()['disponibilite']);
    }

    /** 6. Prix réel affiché depuis MySQL. */
    public function test_real_price_is_reported(): void
    {
        $this->ndole->update(['price' => 999]);
        $this->ndole->refresh();

        $products = $this->catalog()->searchProducts(['ndole']);

        $this->assertSame(999.0, $products->first()['price']);
    }

    /** 7. Producteur réel récupéré. */
    public function test_real_producer_is_reported(): void
    {
        $products = $this->catalog()->searchProducts(['ndole']);

        $this->assertSame('Ferme de Bafia', $products->first()['producer']);
    }

    /** 8. Localisation réelle récupérée. */
    public function test_real_location_is_reported(): void
    {
        $products = $this->catalog()->searchProducts(['ndole']);

        $this->assertSame('Bafia', $products->first()['location']);
    }

    /** 8b. Pas de localisation en base → on ne l'invente pas. */
    public function test_missing_location_is_not_invented(): void
    {
        $this->ndole->update(['location_id' => null]);
        $this->ndole->refresh();

        $products = $this->catalog()->searchProducts(['ndole']);

        $this->assertNull($products->first()['location']);
    }

    /** 9. Synonymes « feuilles amères » / « Ndolé » → recherche pertinente. */
    public function test_synonyms_are_matched(): void
    {
        // Le produit s'appelle « Ndolé », l'utilisateur dit « feuilles amères ».
        // On passe par les termes réellement utilisés par l'assistant
        // (synonymes + sans accents), comme en production.
        $amere = $this->catalog()->searchTermsFor('feuilles amères');
        $this->assertCount(1, $this->catalog()->searchProducts($amere));

        $this->assertCount(1, $this->catalog()->searchProducts($this->catalog()->searchTermsFor('bitterleaf')));
        $this->assertCount(1, $this->catalog()->searchProducts($this->catalog()->searchTermsFor('feuilles de ndole')));

        // ...et via le service d'assistant complet.
        $this->fakeAi('J\'ai trouvé les feuilles de Ndolé.');
        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Est-ce que je peux trouver des feuilles amères ?',
        ]);

        $this->assertSame($this->ndole->id, $resp->json('catalogue.0.id'));
    }

    /** Une correspondance trop faible ne renvoie rien. */
    public function test_weak_match_returns_nothing(): void
    {
        Product::create([
            'producer_id' => $this->producer->id,
            'category_id' => $this->category->id,
            'name' => 'TomateIMPORT',
            'slug' => 'tomate-x',
            'price' => 100, 'unit' => 'kg',
            'stock_quantity' => 10, 'is_available' => true, 'status' => 'published',
        ]);

        // « zebra » ne correspond à aucun produit : aucun résultat plausible.
        $this->assertCount(0, $this->catalog()->searchProducts(['zebra']));
    }

    /** 10. Question de recette → ingrédients connus + recherche catalogue. */
    public function test_recipe_question_searches_each_ingredient(): void
    {
        $this->fakeAi('Pour le Ndolé il vous faut des feuilles de ndolé.', ['Feuilles de ndolé', 'Arachides']);

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Que me faut-il pour préparer un Ndolé ?',
        ]);

        $resp->assertOk();

        // Le plat est connu, et les feuilles de Ndolé sont trouvées en base.
        $this->assertTrue($resp->json('known_dish'));
        $this->assertSame($this->ndole->id, $resp->json('catalogue.0.id'));

        // La recherche par ingrédient distingue ce qui existe de ce qui n'existe pas.
        $results = $this->catalog()->searchForIngredients(['Feuilles de ndolé', 'Viande de pangolin']);
        $this->assertTrue($results[0]['found']);
        $this->assertFalse($results[1]['found']);
        $this->assertSame([], $results[1]['products']);
    }

    /** 11. Aucun résultat → réponse honnête, sans invention. */
    public function test_no_catalog_result_is_honest(): void
    {
        $this->fakeAi('Je ne trouve aucun pangolin sur la plateforme.');

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Vous vendez du pangolin ?',
        ]);

        $resp->assertOk();
        $this->assertSame([], $resp->json('catalogue'));
    }

    /** 12. Produit recommandé → URL réelle du produit. */
    public function test_recommended_product_has_real_url(): void
    {
        $products = $this->catalog()->searchProducts(['ndole']);

        $this->assertSame(route('produit', ['slug' => 'feuilles-ndole']), $products->first()['url']);
        $this->assertSame('feuilles-ndole', $products->first()['slug']);
    }

    /** 13/14 : seuls les clients utilisent l'assistant. */
    public function test_producer_cannot_buy_or_use_assistant(): void
    {
        $producer = User::factory()->create(['role' => 'producer']);

        // Un producteur ne peut pas utiliser l'assistant (protection existante).
        $this->actingAs($producer)
            ->postJson(route('assistant-repas.send'), ['message' => 'des feuilles de ndolé'])
            ->assertForbidden();

        // Et il ne peut toujours pas ajouter au panier (protection existante, intacte).
        $this->actingAs($producer)
            ->post(route('panier.ajouter', ['slug' => 'feuilles-ndole']), ['quantite' => 1])
            ->assertForbidden();
    }

    /** 13bis. Un client, lui, reçoit bien les produits. */
    public function test_client_receives_real_product_proposals(): void
    {
        $this->fakeAi('Voici ce que nous avons.');

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'feuilles de ndolé',
        ]);

        $resp->assertOk();
        $this->assertNotEmpty($resp->json('products'));
        $this->assertSame($this->ndole->id, $resp->json('products.0.id'));
    }

    /** 15. Stock modifié en base → nouvelle question = nouvelle valeur. */
    public function test_changed_stock_is_reflected_on_next_question(): void
    {
        $this->assertSame(30, $this->catalog()->searchProducts(['ndole'])->first()['stock']);

        $this->ndole->update(['stock_quantity' => 4]);
        $this->ndole->refresh();

        $this->assertSame(4, $this->catalog()->searchProducts(['ndole'])->first()['stock']);
    }

    /** 16. Prix modifié en base → nouvelle question = nouveau prix. */
    public function test_changed_price_is_reflected_on_next_question(): void
    {
        $this->assertSame(450.0, $this->catalog()->searchProducts(['ndole'])->first()['price']);

        $this->ndole->update(['price' => 1500]);
        $this->ndole->refresh();

        $this->assertSame(1500.0, $this->catalog()->searchProducts(['ndole'])->first()['price']);
    }

    /** 17-19. Aucun produit / prix / producteur fictif. */
    public function test_nothing_fictitious_is_returned(): void
    {
        $realIds = Product::pluck('id')->all();

        foreach ($this->catalog()->searchProducts(['ndole', 'tomate', 'manioc', 'riz']) as $p) {
            $this->assertContains($p['id'], $realIds, 'Produit inexistant en base retourné.');
            $this->assertNotSame('', $p['name']);
            $this->assertGreaterThan(0, $p['price']);
            $this->assertNotSame('', $p['producer']);
            $this->assertStringContainsString($p['slug'], $p['url']);
        }
    }

    /** Localisation demandée et réellement existante. */
    public function test_requested_existing_place_is_used_as_filter(): void
    {
        $this->assertSame('Bafia', $this->catalog()->resolvePlace('Je cherche du ndolé à Bafia'));

        // Un lieu inconnu n'est pas inventé.
        $this->assertNull($this->catalog()->resolvePlace('Je cherche du ndolé à Cocody-maritime'));
    }

    /** La panne de l'IA ne masque pas la recherche catalogue. */
    public function test_ai_outage_still_exposes_real_catalogue(): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);
        Http::fake(['openrouter.ai/*' => Http::response('', 503)]);

        $resp = $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'Avez-vous des feuilles de ndolé ?',
        ]);

        $resp->assertOk();

        // On ne prétend PAS avoir répondu par l'IA…
        $this->assertTrue($resp->json('api_error'));

        // …mais le catalogue réellement interrogé reste disponible et exact.
        $this->assertTrue($resp->json('catalogue_checked'));
        $this->assertSame($this->ndole->id, $resp->json('catalogue.0.id'));
        $this->assertEquals(450.0, $resp->json('catalogue.0.price'));
    }

    /** Le prompt interdit explicitement la phrase « pas d'accès aux stocks ». */
    public function test_prompt_forbids_claiming_no_stock_access(): void
    {
        config(['services.openrouter.key' => 'sk-or-test-key']);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'reply' => 'J\'ai trouvé vos feuilles.', 'ingredients' => [],
                ])]]],
            ]),
        ]);

        $this->actingAs($this->client)->postJson(route('assistant-repas.send'), [
            'message' => 'feuilles de ndolé',
        ])->assertOk();

        Http::assertSent(function ($request) {
            $body = (string) $request->body();

            // Le catalogue réel est injecté dans le prompt…
            $body = json_decode((string) $request->body(), true);
            $text = json_encode($body, JSON_UNESCAPED_UNICODE);

            $this->assertStringContainsString('Feuilles de Ndolé fraîches', $text);
            $this->assertStringContainsString('450', $text);
            $this->assertStringContainsString('Bafia', $text);

            // …et l'IA reçoit l'interdiction formelle du faux refus.
            $this->assertStringContainsString("n'ai pas accès aux stocks", $text);

            return true;
        });
    }
}