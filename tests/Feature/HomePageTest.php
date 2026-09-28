<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_contains_public_marketing_sections(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Agro Marcket')
            ->assertSee('Produits disponibles')
            ->assertSee('Des produits frais, locaux et dignes de confiance.');
    }

    public function test_home_page_can_filter_products_from_free_text_recommendation(): void
    {
        $response = $this->get('/?recherche=je+veux+quelque+chose+de+local+et+fruit');

        $response->assertStatus(200)
            ->assertSee('Plantain doux')
            ->assertSee('Avocat Hass');
    }

    public function test_authenticated_client_sees_client_actions_and_visible_ai_recommendation(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email' => 'client.home@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200)
            ->assertSee('Assistant IA')
            ->assertSee('Mes commandes')
            ->assertSee('Mon panier')
            ->assertDontSee('Se connecter');
    }

    public function test_authenticated_producer_sees_producer_actions(): void
    {
        $user = User::factory()->create([
            'role' => 'producer',
            'email' => 'producer.home@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200)
            ->assertSee('Mon tableau de bord')
            ->assertSee('Mes produits')
            ->assertDontSee('Se connecter');
    }
}
