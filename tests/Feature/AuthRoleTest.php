<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_registration_form(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Créer un compte');
    }

    public function test_user_can_register_as_client(): void
    {
        $response = $this->post('/register', [
            'name' => 'Client Test',
            'email' => 'client@example.com',
            'phone' => '650000001',
            'role' => 'client',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/client/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'client@example.com',
            'role' => 'client',
        ]);
    }

    public function test_producer_user_cannot_access_client_dashboard(): void
    {
        $producer = User::factory()->create([
            'role' => 'producer',
        ]);

        $this->actingAs($producer)
            ->get('/client/dashboard')
            ->assertStatus(403);
    }
}
