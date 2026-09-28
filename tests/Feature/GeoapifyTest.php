<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Geolocalisation Geoapify.
 *
 * L'API n'est JAMAIS appelée réellement : Http::fake() partout. Ces tests
 * couvrent le contrat du service, le passage par les routes Laravel (la clé
 * ne doit jamais sortir), et tous les cas d'erreur.
 */
class GeoapifyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear();
        config(['services.geoapify.key' => 'test-geoapify-key']);
    }

    /** Réponse Geoapify réaliste pour Yaoundé. */
    private function reverseYaounde(): array
    {
        return [
            'results' => [[
                'lat' => 3.8480, 'lon' => 11.5021,
                'formatted' => 'Yaounde, Centre, Cameroun',
                'country' => 'Cameroun', 'country_code' => 'cm',
                'state' => 'Centre', 'county' => 'Mfoundi',
                'city' => 'Yaounde', 'suburb' => 'Ngoa-Ekele',
            ]],
        ];
    }

    /* --------------------- Géocodage inverse --------------------- */

    public function test_reverse_returns_structured_place(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response($this->reverseYaounde())]);

        $place = app(\App\Services\GeoapifyService::class)->reverse(3.8480, 11.5021);

        $this->assertSame(3.8480, $place['latitude']);
        $this->assertSame(11.5021, $place['longitude']);
        $this->assertSame('Yaounde', $place['city']);
        $this->assertSame('Centre', $place['region']);
        $this->assertSame('Cameroun', $place['country']);
        $this->assertSame('Ngoa-Ekele', $place['locality']);
    }

    public function test_reverse_falls_back_to_village_when_no_city(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response(['results' => [[
            'lat' => 4.7399, 'lon' => 11.2206,
            'formatted' => 'Bafia, Cameroun',
            'country_code' => 'cm', 'state' => 'Centre', 'village' => 'Bafia',
        ]]])]);

        $place = app(\App\Services\GeoapifyService::class)->reverse(4.7399, 11.2206);

        // Pas de 'city' chez Geoapify hors des grandes villes : on retombe sur village.
        $this->assertSame('Bafia', $place['city']);
        // country_code 'cm' doit devenir le libelle lisible stocke en base.
        $this->assertSame('Cameroun', $place['country']);
    }

    public function test_reverse_sends_api_key_in_query_never_in_body(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response($this->reverseYaounde())]);

        app(\App\Services\GeoapifyService::class)->reverse(3.8480, 11.5021);

        Http::assertSent(function ($request) {
            $this->assertStringContainsString('apiKey=test-geoapify-key', $request->url());
            $this->assertSame('', (string) $request->body());
            $this->assertStringContainsString('lang=fr', $request->url());

            return true;
        });
    }

    public function test_reverse_results_are_cached(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response($this->reverseYaounde())]);

        $svc = app(\App\Services\GeoapifyService::class);
        $svc->reverse(3.8480, 11.5021);
        $svc->reverse(3.8480, 11.5021);
        $svc->reverse(3.8480, 11.5021);

        // Un seul appel reseau malgre trois demandes identiques.
        Http::assertSentCount(1);
    }

    /* --------------------- Recherche d'adresse --------------------- */

    public function test_search_returns_suggestions(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response([
            'results' => [
                ['lat' => 3.8480, 'lon' => 11.5021, 'formatted' => 'Yaounde, Cameroun',
                 'country_code' => 'cm', 'state' => 'Centre', 'city' => 'Yaounde'],
            ],
        ])]);

        $places = app(\App\Services\GeoapifyService::class)->search('Yaounde, Cameroun');

        $this->assertCount(1, $places);
        $this->assertSame('Yaounde', $places[0]['city']);
    }

    public function test_search_with_no_result_returns_readable_error(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response(['results' => []])]);

        $this->expectException(\RuntimeException::class);

        app(\App\Services\GeoapifyService::class)->search('endroit inexistant xyz');
    }

    public function test_search_rejects_too_short_query(): void
    {
        Http::fake();

        $this->expectException(\RuntimeException::class);

        app(\App\Services\GeoapifyService::class)->search('ab');

        // Aucun appel doit partir pour une saisie trop courte.
        Http::assertNothingSent();
    }

    /* --------------------- Coordonnées invalides --------------------- */

    public function test_out_of_range_coordinates_are_refused_without_api_call(): void
    {
        Http::fake();

        $svc = app(\App\Services\GeoapifyService::class);

        try {
            $svc->reverse(999.0, 11.5);
            $this->fail('Latitude hors bornes devrait etre refusee.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('invalide', mb_strtolower($e->getMessage()));
        }

        try {
            $svc->reverse(3.8, 999.0);
            $this->fail('Longitude hors bornes devrait etre refusee.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('invalide', mb_strtolower($e->getMessage()));
        }

        Http::assertNothingSent();
    }

    public function test_zero_coordinates_are_refused(): void
    {
        Http::fake();

        $this->expectException(\RuntimeException::class);

        // 0,0 = Golfe de Guinee : jamais une position utilisateur plausible.
        app(\App\Services\GeoapifyService::class)->reverse(0.0, 0.0);
    }

    /* --------------------- Cas d'erreur API --------------------- */

    public function test_missing_key_is_reported_without_calling_api(): void
    {
        config(['services.geoapify.key' => '']);
        Http::fake();

        $svc = app(\App\Services\GeoapifyService::class);
        $this->assertFalse($svc->isConfigured());

        $this->expectException(\RuntimeException::class);
        $svc->reverse(3.8480, 11.5021);
    }

    public function test_unauthorized_response_is_translated(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response([
            'error' => ['code' => 401, 'message' => 'Invalid API key'],
        ], 401)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/non configure ou cle invalide/i');

        app(\App\Services\GeoapifyService::class)->reverse(3.8480, 11.5021);
    }

    public function test_rate_limit_is_translated(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response([
            'error' => ['code' => 429, 'message' => 'quota exceeded'],
        ], 429)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/trop de recherches/i');

        app(\App\Services\GeoapifyService::class)->reverse(3.8480, 11.5021);
    }

    public function test_server_error_is_translated(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response('', 500)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/indisponible/i');

        app(\App\Services\GeoapifyService::class)->reverse(3.8480, 11.5021);
    }

    public function test_empty_response_is_translated(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response(['results' => []])]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/non reconnue/i');

        app(\App\Services\GeoapifyService::class)->reverse(3.8480, 11.5021);
    }

    /* --------------------- Routes HTTP --------------------- */

    public function test_reverse_route_never_exposes_the_key(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response($this->reverseYaounde())]);

        $response = $this->postJson(route('geolocation.reverse'), [
            'latitude' => 3.8480, 'longitude' => 11.5021,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $body = $response->getContent();
        $this->assertStringNotContainsString('test-geoapify-key', $body);
        $this->assertStringNotContainsString(config('services.geoapify.base_url'), $body);
        $this->assertSame('Yaounde', $response->json('place.city'));
    }

    public function test_search_route_never_exposes_the_key(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response(['results' => [
            ['lat' => 3.8, 'lon' => 11.5, 'formatted' => 'Yaounde', 'country_code' => 'cm', 'city' => 'Yaounde'],
        ]])]);

        $response = $this->getJson(route('geolocation.search', ['query' => 'Yaounde Cameroun']));

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertStringNotContainsString('test-geoapify-key', $response->getContent());
    }

    public function test_routes_return_readable_error_instead_of_500(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response('', 503)]);

        $response = $this->postJson(route('geolocation.reverse'), [
            'latitude' => 3.8480, 'longitude' => 11.5021,
        ]);

        // Jamais de 500 : le front affiche un message comprenable.
        $response->assertOk()
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'message']);
    }

    public function test_routes_return_503_when_key_missing(): void
    {
        config(['services.geoapify.key' => '']);
        Http::fake();

        $this->postJson(route('geolocation.reverse'), ['latitude' => 3.8, 'longitude' => 11.5])
            ->assertStatus(503)
            ->assertJson(['ok' => false]);

        $this->getJson(route('geolocation.search', ['query' => 'Yaounde']))
            ->assertStatus(503)
            ->assertJson(['ok' => false]);
    }

    public function test_routes_validate_coordinates(): void
    {
        Http::fake();

        $this->postJson(route('geolocation.reverse'), ['latitude' => 999, 'longitude' => 11])
            ->assertStatus(422)
            ->assertJsonValidationErrors('latitude');

        $this->postJson(route('geolocation.reverse'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);

        $this->getJson(route('geolocation.search', ['query' => 'ab']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('query');

        Http::assertNothingSent();
    }

    public function test_routes_work_without_authentication_registration_is_public(): void
    {
        Http::fake(['api.geoapify.com/*' => Http::response($this->reverseYaounde())]);

        // L'etape 3 de l'inscription est publique : le GPS doit y fonctionner.
        $this->postJson(route('geolocation.reverse'), [
            'latitude' => 3.8480, 'longitude' => 11.5021,
        ])->assertOk()->assertJson(['ok' => true]);
    }
}
