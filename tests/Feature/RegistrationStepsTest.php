<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RegisterProgress;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Registration step-by-step hardening.
 *
 * Runs against MySQL with DatabaseTransactions (never migrate:fresh),
 * so no existing data is dropped.
 */
class RegistrationStepsTest extends TestCase
{
    use DatabaseTransactions;

    private function chooseProducer(): void
    {
        $this->postJson('/register/step1', ['role' => 'producer'])->assertOk();
    }

    private function chooseClient(): void
    {
        $this->postJson('/register/step1', ['role' => 'client'])->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function validStep2(): array
    {
        return [
            'first_name' => 'Jean',
            'last_name' => 'Ngono',
            'gender' => 'male',
            'date_of_birth' => '1990-05-10',
            'phone' => '655112233',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validStep3(): array
    {
        return [
            'country' => 'Cameroun',
            'region' => 'Centre',
            'city' => 'Bafia',
            'locality' => 'Melen',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validStep4(): array
    {
        return [
            'activity_type' => 'Cacao',
            'specialty' => 'Fermentation',
            'main_products' => 'Cacao',
            'farm_name' => 'Ferme Jean',
            'years_experience' => 10,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validStep6(string $email = 'producer.test@example.test'): array
    {
        return [
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accepted_terms' => '1',
        ];
    }

    private function fillProducerUntil(int $step): void
    {
        $this->chooseProducer();
        $this->postJson('/register/step2', $this->validStep2())->assertOk();

        if ($step >= 3) {
            $this->postJson('/register/step3', $this->validStep3())->assertOk();
        }
        if ($step >= 4) {
            $this->postJson('/register/step4', $this->validStep4())->assertOk();
        }
        if ($step >= 5) {
            $this->submitCni();
        }
        if ($step >= 6) {
            $this->postJson('/register/step6', $this->validStep6())->assertOk();
        }
    }

    /**
     * The recap is a GET endpoint; JSON recap assertions read the same payload
     * the OTP sender uses.
     */
    private function recapPayload(): array
    {
        return $this->getJson('/register/recap')->assertOk()->json();
    }

    /**
     * Step 5 carries a file upload: it must use multipart, never postJson().
     */
    private function submitCni(string $number = '1002938475'): void
    {
        $this->post('/register/step5', [
            'cni_number' => $number,
            'cni_document' => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();
    }

    // TEST 1 — tous les champs obligatoires remplis -> passage autorisé
    public function test_1_complete_producer_flow_reaches_otp(): void
    {
        $this->fillProducerUntil(6);

        $this->recapPayload();

        $otp = $this->postJson('/register/send-otp');
        $otp->assertOk()->assertJsonPath('target_email', 'producer.test@example.test');
    }

    // TEST 2 — un champ obligatoire vide -> passage refusé
    public function test_2_missing_required_field_blocks_step(): void
    {
        $this->chooseProducer();

        $payload = $this->validStep2();
        unset($payload['last_name']);

        $this->postJson('/register/step2', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('last_name');

        $this->assertNull(session('register.step2'));
    }

    // TEST 3 — plusieurs champs obligatoires vides -> passage refusé + erreurs
    public function test_3_multiple_missing_fields_report_all_errors(): void
    {
        $this->chooseProducer();

        $this->postJson('/register/step2', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'gender', 'date_of_birth', 'phone']);

        $this->postJson('/register/step3', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['country', 'region', 'city']);
    }

    // TEST 4 — conditions non cochées -> passage refusé
    public function test_4_terms_not_accepted_blocks_step6(): void
    {
        $this->fillProducerUntil(5);

        $payload = $this->validStep6();
        $payload['accepted_terms'] = '';

        $this->postJson('/register/step6', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('accepted_terms');

        $this->assertNull(session('register.step6'));

        // Same with the key entirely absent.
        unset($payload['accepted_terms']);
        $this->postJson('/register/step6', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('accepted_terms');
    }

    // TEST 5 — conditions cochées -> passage autorisé
    public function test_5_terms_accepted_allows_step6(): void
    {
        $this->fillProducerUntil(5);

        $this->postJson('/register/step6', $this->validStep6())
            ->assertOk()
            ->assertJsonPath('email', 'producer.test@example.test');

        $this->assertSame('producer.test@example.test', session('register.step6.email'));
    }

    // TEST 6 — email vide -> refus
    public function test_6_empty_email_is_rejected(): void
    {
        $this->fillProducerUntil(5);

        $payload = $this->validStep6();
        $payload['email'] = '';

        $this->postJson('/register/step6', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    // TEST 7 — email invalide -> refus
    public function test_7_invalid_email_is_rejected(): void
    {
        $this->fillProducerUntil(5);

        $payload = $this->validStep6();
        $payload['email'] = 'pas-un-email';

        $this->postJson('/register/step6', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    // TEST 8 — l'email affiché dans le récap == email utilisé pour l'envoi du code
    public function test_8_recap_email_matches_the_email_used_to_send_the_code(): void
    {
        $this->fillProducerUntil(6);

        $recap = $this->recapPayload();
        $recapEmail = $recap['step6']['email'] ?? null;

        $otp = $this->postJson('/register/send-otp')->assertOk();

        $this->assertSame('producer.test@example.test', $recapEmail);
        $this->assertSame($recapEmail, $otp->json('target_email'));

        // The recap must never leak the CNI document path or number.
        $this->assertArrayNotHasKey('cni_path', $recap['step5']);
        $this->assertArrayNotHasKey('cni_number', $recap['step5']);
        $this->assertTrue($recap['step5']['provided']);
    }

    // TEST 9 — finalisation avec une étape précédente incomplète -> refus
    public function test_9_finalisation_with_incomplete_previous_step_is_refused(): void
    {
        $this->fillProducerUntil(4); // steps 5 and 6 missing
        $this->postJson('/register/step6', $this->validStep6())->assertStatus(422);

        $this->postJson('/register/step8', ['otp' => '123456'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('finalize');

        $this->assertDatabaseMissing('users', ['email' => 'producer.test@example.test']);
    }

    // TEST 10 — saut direct d'une étape -> refus
    public function test_10_skipping_steps_is_refused(): void
    {
        $this->chooseProducer();

        // Jump straight from step 1 to step 6.
        $this->postJson('/register/step6', $this->validStep6())
            ->assertStatus(422)
            ->assertJsonPath('blocked_step', 2);

        // Jump straight to step 4 without step 3.
        $this->postJson('/register/step2', $this->validStep2())->assertOk();

        $this->postJson('/register/step4', $this->validStep4())
            ->assertStatus(422)
            ->assertJsonPath('blocked_step', 3);

        // And send-otp is blocked too.
        $this->postJson('/register/send-otp')
            ->assertStatus(422)
            ->assertJsonPath('blocked_step', 3);
    }

    // TEST 11 — modification d'une étape précédente -> données conservées, validation recalculée
    public function test_11_going_back_keeps_data_and_recomputes_validation(): void
    {
        $this->fillProducerUntil(6);

        $this->assertSame('Bafia', session('register.step3.city'));

        // Modify step 3 -> data updated, everything else preserved.
        $this->postJson('/register/step3', [
            'country' => 'Cameroun',
            'region' => 'Littoral',
            'city' => 'Douala',
        ])->assertOk();

        $this->assertSame('Douala', session('register.step3.city'));
        $this->assertSame('Cacao', session('register.step4.activity_type'));
        $this->assertSame('producer.test@example.test', session('register.step6.email'));

        // Now break the freshly re-validated step: empty city is refused.
        $this->postJson('/register/step3', ['country' => 'Cameroun', 'region' => 'Littoral', 'city' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('city');

        // Previous value is kept, not wiped.
        $this->assertSame('Douala', session('register.step3.city'));
    }

    // TEST 12 — Producteur incomplet -> compte non finalisé
    public function test_12_incomplete_producer_cannot_finalize(): void
    {
        $this->chooseProducer();
        $this->postJson('/register/step2', $this->validStep2())->assertOk();
        $this->postJson('/register/step3', $this->validStep3())->assertOk();
        $this->postJson('/register/step4', $this->validStep4())->assertOk();
        // No CNI (step 5), no security (step 6).

        $this->postJson('/register/step8', ['otp' => '123456'])->assertStatus(422);

        $this->assertSame(0, User::where('email', 'producer.test@example.test')->count());
    }

    // TEST 13 — le Client ne subit pas les champs Producteur
    public function test_13_client_does_not_need_producer_fields(): void
    {
        $this->chooseClient();
        $this->postJson('/register/step2', $this->validStep2())->assertOk();
        $this->postJson('/register/step3', $this->validStep3())->assertOk();


        $email = 'client.steps@example.test';
        $this->postJson('/register/step6', $this->validStep6($email))->assertOk();

        $otp = $this->postJson('/register/send-otp')->assertOk();
        $this->assertSame($email, $otp->json('target_email'));

        // Producer-only steps stay closed to a client.
        $this->postJson('/register/step4', $this->validStep4())
            ->assertStatus(422)
            ->assertJsonPath('errors.role.0', 'Cette étape est réservée au profil Producteur.');

        $this->postJson('/register/step5', ['cni_number' => '1002938475'])
            ->assertStatus(422)
            ->assertJsonPath('errors.role.0', 'Cette étape est réservée au profil Producteur.');

        // Producer-only data must not be written for a client either.
        $this->assertNull(session('register.step4'));
        $this->assertNull(session('register.step5'));

        $this->postJson('/register/step8', ['otp' => '123456'])->assertOk();

        $client = User::where('email', $email)->firstOrFail();
        $this->assertSame('client', $client->role);
        $this->assertNull($client->producerProfile);
        $this->assertNull($client->producerVerification);
    }

    // TEST 14 — données manipulées par HTTP -> validation serveur toujours appliquée
    public function test_14_direct_http_manipulation_is_rejected(): void
    {
        // Full bypass attempt in a single session, skipping everything.
        $this->chooseProducer();

        $this->postJson('/register/send-otp')->assertStatus(422);
        $this->postJson('/register/step8', ['otp' => '123456'])->assertStatus(422);

        // Injecting an email into the recap request changes nothing server-side.
        $this->postJson('/register/recap', ['email' => 'hacker@example.test'])->assertStatus(405);
        $this->getJson('/register/recap')
            ->assertOk()
            ->assertJsonPath('step6.email', '');

        // Accepted terms forced via HTTP on a step that was never reached: refused.
        $this->postJson('/register/step6', $this->validStep6('hacker@example.test'))
            ->assertStatus(422);

        $this->assertSame(0, User::where('email', 'hacker@example.test')->count());
    }

    // TEST 15 — aucune création de compte tant que tout n'est pas valide
    public function test_15_no_account_is_created_while_any_required_data_is_missing(): void
    {
        $this->chooseProducer();
        $this->postJson('/register/step2', $this->validStep2())->assertOk();
        $this->postJson('/register/step3', $this->validStep3())->assertOk();
        $this->postJson('/register/step4', $this->validStep4())->assertOk();

        $email = 'partial.producer@example.test';
        $this->postJson('/register/step6', $this->validStep6($email))->assertStatus(422);

        // Even with a valid OTP attempt, no user row may exist.
        $this->postJson('/register/step8', ['otp' => '123456'])->assertStatus(422);
        $this->assertSame(0, User::where('email', $email)->count());

        // Now complete step 5, then everything succeeds.
        $this->submitCni();

        $this->postJson('/register/step6', $this->validStep6($email))->assertOk();
        $this->postJson('/register/send-otp')->assertOk();
        $this->postJson('/register/step8', ['otp' => '123456'])->assertOk();

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertSame('producer', $user->role);
        $this->assertSame('pending', $user->producerVerification->status);
    }

    // Garde-fou du service de progression
    public function test_16_progress_service_reports_the_first_incomplete_step(): void
    {
        $progress = app(RegisterProgress::class);

        $this->chooseProducer();
        $this->assertSame(2, $progress->firstBlockingStepBefore(request(), 'producer', 6)['step']);

        $this->postJson('/register/step2', $this->validStep2())->assertOk();
        $this->assertSame(3, $progress->firstBlockingStepBefore(request(), 'producer', 6)['step']);

        $this->postJson('/register/step3', $this->validStep3())->assertOk();
        $this->postJson('/register/step4', $this->validStep4())->assertOk();
        $this->assertSame(5, $progress->firstBlockingStepBefore(request(), 'producer', 6)['step']);
    }
}
