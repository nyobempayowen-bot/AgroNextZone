<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_producer_registration_persists_public_profile_and_private_verification(): void
    {
        $response = $this->withSession([
            'register.role' => 'producer',
            'register.step2' => [
                'first_name' => 'Amina',
                'last_name' => 'Ngono',
                'gender' => 'female',
                'date_of_birth' => '1990-01-15',
                'phone' => '690000001',
            ],
            'register.step3' => [
                'country' => 'Cameroun',
                'region' => 'Centre',
                'city' => 'Bafia',
                'farm_location' => 'Route Bokito Km 3',
            ],
            'register.step4' => [
                'activity_type' => 'Cacao',
                'specialty' => 'Fermentation',
                'main_products' => 'Cacao',
                'farm_name' => 'Ferme Amina',
                'years_experience' => 12,
                'description' => 'Productrice de cacao.',
            ],
            'register.step5' => [
                'cni_number' => '1002938475',
                'cni_path' => 'private_cni/cni_test.pdf',
                'cni_file_name' => 'cni_test.pdf',
            ],
            'register.step6' => [
                'email' => 'amina@example.com',
                'password' => 'Password123!',
            ],
        ])->postJson('/register/step8', ['otp' => '123456']);

        $response->assertOk();

        $producer = User::where('email', 'amina@example.com')->firstOrFail();
        $this->assertSame('Amina', $producer->profile->first_name);
        $this->assertSame('Cacao', $producer->producerProfile->activity_type);
        $this->assertSame('pending', $producer->producerVerification->status);
        $this->assertSame('1002938475', $producer->producerVerification->cni_number);
        $this->assertArrayNotHasKey('cni_number', $producer->producerVerification->toArray());
        $this->assertDatabaseHas('locations', ['user_id' => $producer->id, 'address' => 'Route Bokito Km 3']);
    }

    public function test_producer_can_update_a_persistent_professional_profile(): void
    {
        $producer = User::factory()->create(['role' => 'producer']);
        $producer->locations()->create([
            'country' => 'Cameroun',
            'region' => 'Centre',
            'city' => 'Bafia',
            'is_primary' => true,
        ]);

        $this->actingAs($producer)->post('/producer/account', [
            'name' => 'Producer Test',
            'phone' => '690000002',
            'activity_type' => 'Maraichage',
            'specialty' => 'Tomates',
            'main_products' => 'Tomates, gombo',
            'farm_name' => 'Ferme Test',
            'farm_location' => 'Bafia Nord',
            'years_experience' => 8,
            'description' => 'Production locale.',
        ])->assertRedirect('/producer/dashboard?tab=account');

        $producer->refresh();
        $this->assertSame('Maraichage', $producer->producerProfile->activity_type);
        $this->assertSame(8, $producer->producerProfile->years_experience);
        $this->assertSame('Bafia Nord', $producer->locations()->where('is_primary', true)->value('address'));
    }
}
