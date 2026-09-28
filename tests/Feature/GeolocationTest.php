<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bloc C — Geolocation tests.
 *
 * - Region filtering returns only producers/products of that region;
 * - A page renders fine even when producers have no coordinates;
 * - The map block appears only when geolocation data exists.
 */
class GeolocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_region_filter_returns_only_products_of_that_region(): void
    {
        $northProducer = User::factory()->create(['role' => 'producer', 'region' => 'Nord']);
        $westProducer  = User::factory()->create(['role' => 'producer', 'region' => 'Ouest']);

        $inRegion  = Product::factory()->create(['producer_id' => $northProducer->id, 'name' => 'Igname Nord']);
        $elsewhere = Product::factory()->create(['producer_id' => $westProducer->id, 'name' => 'Patate Ouest']);

        $response = $this->get('/?region=Nord');

        $response->assertOk();
        $response->assertSee('Igname Nord');
        $response->assertDontSee('Patate Ouest');
    }

    public function test_page_renders_gracefully_without_coordinates(): void
    {
        // Producer with region but NO latitude/longitude.
        $producer = User::factory()->create([
            'role' => 'producer',
            'region' => 'Centre',
            'adresse' => 'Yaoundé',
            'latitude' => null,
            'longitude' => null,
        ]);
        Product::factory()->create(['producer_id' => $producer->id]);

        $response = $this->get('/?region=Centre');

        $response->assertOk();
        // No coordinates → no map section.
        $response->assertDontSee('id="map"', false);
    }

    public function test_map_is_displayed_when_coordinates_exist(): void
    {
        $producer = User::factory()->create([
            'role' => 'producer',
            'region' => 'Centre',
            'adresse' => 'Yaoundé',
            'latitude' => 3.848,
            'longitude' => 11.502,
        ]);
        Product::factory()->create(['producer_id' => $producer->id]);

        $response = $this->get('/?region=Centre');

        $response->assertOk();
        $response->assertSee('id="map"', false);
        // Google Maps JavaScript API (Bloc C — API réelle).
        $response->assertSee('maps.googleapis.com', false);
    }
}
