<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create(['role' => 'producer']);
        $this->seed();
    }

    public function test_product_detail_page_loads(): void
    {
        $response = $this->get('/produit/cacao-fermente-qualite-superieure');

        $response->assertStatus(200)
            ->assertSee('Cacao fermenté qualité supérieure')
            ->assertSee('Bafia')
            ->assertSee('Nkwenti Agric &amp; Fils')
            ->assertSee('aspect-square')
            ->assertSee('object-contain');
    }

    public function test_client_can_see_add_to_cart_on_multiple_product_details(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        foreach (['cacao-fermente-qualite-superieure', 'plantain-doux-ebolowa'] as $slug) {
            $this->actingAs($client)
                ->get('/produit/' . $slug)
                ->assertOk()
                ->assertSee('Ajouter au panier')
                ->assertSee('object-contain');
        }
    }

    public function test_producer_can_view_details_without_purchase_action(): void
    {
        $producer = User::factory()->create(['role' => 'producer']);

        $this->actingAs($producer)
            ->get('/produit/plantain-doux-ebolowa')
            ->assertOk()
            ->assertSee('Plantations Mireille N.')
            ->assertDontSee('Ajouter au panier');
    }
}
