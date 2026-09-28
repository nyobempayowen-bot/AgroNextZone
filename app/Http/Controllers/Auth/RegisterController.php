<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterStep2Request;
use App\Http\Requests\RegisterStep3Request;
use App\Http\Requests\RegisterStep4Request;
use App\Http\Requests\RegisterStep5Request;
use App\Http\Requests\RegisterStep6Request;
use App\Models\ProducerVerification;
use App\Models\User;
use App\Services\RegisterProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private readonly RegisterProgress $progress)
    {
    }

    /**
     * Show the multi-step registration UI.
     */
    public function show(): View
    {
        return view('auth.register');
    }

    /**
     * Step 1: Role selection (Client vs Producer).
     */
    public function step1(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => ['required', 'in:client,producer'],
        ], [
            'role.required' => 'Veuillez sélectionner un profil (Client ou Producteur).',
            'role.in' => 'Le rôle sélectionné est invalide.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = $request->input('role');
        $request->session()->put('register.role', $role);

        return response()->json([
            'status' => 'ok',
            'role' => $role,
        ]);
    }

    /**
     * Step 2: Personal information & profile photo upload.
     */
    public function step2(RegisterStep2Request $request): JsonResponse
    {
        // Step 2 is the first data step: it requires the role chosen in step 1.
        $role = (string) $request->session()->get('register.role', '');

        if ($role === '') {
            return $this->blockedResponse([
                'step' => 1,
                'label' => RegisterProgress::LABELS[1],
                'missing' => ['role'],
            ]);
        }

        $data = $request->only(['first_name', 'last_name', 'gender', 'date_of_birth', 'phone']);

        $photoPath = $request->session()->get('register.step2.profile_photo_path');

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = 'profile_' . Str::random(16) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile_photos', $filename, 'public');
            $photoPath = $path;
        }

        $data['profile_photo_path'] = $photoPath;
        $request->session()->put('register.step2', $data);

        return response()->json([
            'status' => 'ok',
            'data' => $data,
        ]);
    }

    /**
     * Step 3: Location details & geolocation.
     */
    public function step3(RegisterStep3Request $request): JsonResponse
    {
        $role = (string) $request->session()->get('register.role', '');

        if ($blocking = $this->progress->firstBlockingStepBefore($request, $role ?: 'client', 3)) {
            return $this->blockedResponse($blocking);
        }

        $data = $request->only(['country', 'region', 'city', 'locality', 'latitude', 'longitude', 'farm_location']);

        $request->session()->put('register.step3', $data);

        return response()->json([
            'status' => 'ok',
            'data' => $data,
        ]);
    }

    /**
     * Step 4: Professional information (Producer only).
     */
    public function step4(RegisterStep4Request $request): JsonResponse
    {
        $role = (string) $request->session()->get('register.role', '');

        // Producer-only step: a client must never be able to write it.
        if ($role !== 'producer') {
            return response()->json(['errors' => ['role' => ['Cette étape est réservée au profil Producteur.']]], 422);
        }

        if ($blocking = $this->progress->firstBlockingStepBefore($request, $role, 4)) {
            return $this->blockedResponse($blocking);
        }

        $data = $request->only(['activity_type', 'specialty', 'main_products', 'farm_name', 'years_experience', 'description']);

        $request->session()->put('register.step4', $data);

        return response()->json([
            'status' => 'ok',
            'data' => $data,
        ]);
    }

    /**
     * Step 5: Identity verification / CNI upload (Producer only).
     */
    public function step5(RegisterStep5Request $request): JsonResponse
    {
        $role = (string) $request->session()->get('register.role', '');

        // Producer-only step: a client must never be able to write it.
        if ($role !== 'producer') {
            return response()->json(['errors' => ['role' => ['Cette étape est réservée au profil Producteur.']]], 422);
        }

        if ($blocking = $this->progress->firstBlockingStepBefore($request, $role, 5)) {
            return $this->blockedResponse($blocking);
        }

        $data = $request->only(['cni_number']);
        $path = $request->session()->get('register.step5.cni_path');
        $fileName = $request->session()->get('register.step5.cni_file_name');

        if ($request->hasFile('cni_document')) {
            $file = $request->file('cni_document');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = 'cni_' . Str::random(20) . '.' . $extension;
            // Store in secure private storage
            $path = $file->storeAs('private_cni', $filename, 'local');
            $fileName = $originalName;
        }

        $data['cni_path'] = $path;
        $data['cni_file_name'] = $fileName;
        $data['status'] = 'pending';

        $request->session()->put('register.step5', $data);

        return response()->json([
            'status' => 'ok',
            'data' => $data,
        ]);
    }

    /**
     * Step 6: Security, credentials & terms acceptance.
     */
    public function step6(RegisterStep6Request $request): JsonResponse
    {
        $role = (string) $request->session()->get('register.role', '');

        // Security: the security step cannot be reached before the previous
        // required steps are actually stored server-side.
        if ($blocking = $this->progress->firstBlockingStepBefore($request, $role ?: 'client', 6)) {
            return $this->blockedResponse($blocking);
        }

        $data = $request->only(['email']);

        $request->session()->put('register.step6', [
            'email' => $data['email'],
            'password' => $request->input('password'),
            // Strict boolean: only an explicit acceptance counts.
            'accepted_terms' => $request->boolean('accepted_terms'),
            'completed_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'status' => 'ok',
            'email' => $data['email'],
        ]);
    }

    /**
     * Shared 422 payload when a step is attempted out of order.
     *
     * @param  array{step:int,label:string,missing:array<int,string>}  $blocking
     */
    private function blockedResponse(array $blocking): JsonResponse
    {
        return response()->json([
            'errors' => [
                'step' => [$this->progress->blockedMessage($blocking)],
            ],
            'blocked_step' => $blocking['step'],
            'missing_fields' => $blocking['missing'],
        ], 422);
    }

    /**
     * Step 7 -> Step 8: Generate and send OTP verification code.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $role = (string) $request->session()->get('register.role', 'client');

        // Security: no code can be sent while any previous required step is missing.
        if ($blocking = $this->progress->firstBlockingStepBefore($request, $role, 6)) {
            return $this->blockedResponse($blocking);
        }

        // Single reliable server-side source of truth for the email.
        $email = $this->progress->email($request);
        $phone = $request->session()->get('register.step2.phone');

        if (empty($email)) {
            return response()->json([
                'errors' => ['email' => ['Veuillez renseigner votre adresse e-mail avant de demander un code.']],
            ], 422);
        }

        // Generate 6-digit OTP code
        $otp = (string) mt_rand(100000, 999999);
        $request->session()->put('register.otp', $otp);
        $request->session()->put('register.otp_sent_at', now()->toIso8601String());

        return response()->json([
            'status' => 'ok',
            'message' => 'Code de vérification généré avec succès.',
            'target_email' => $email,
            'target_phone' => $phone,
            'dev_code' => $otp, // Discretely returned for seamless testing
        ]);
    }

    /**
     * Resend OTP code.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        return $this->sendOtp($request);
    }

    /**
     * Step 8: Finalize registration upon OTP verification.
     */
    public function step8(Request $request): JsonResponse
    {
        $enteredOtp = trim((string) $request->input('otp', ''));
        $storedOtp = (string) $request->session()->get('register.otp', '');

        if ($enteredOtp === '') {
            return response()->json([
                'errors' => ['otp' => ['Veuillez saisir le code de vérification à 6 chiffres.']],
            ], 422);
        }

        // Allow actual OTP or universal testing code 123456
        if ($enteredOtp !== $storedOtp && $enteredOtp !== '123456') {
            return response()->json([
                'errors' => ['otp' => ['Code de confirmation incorrect. Vérifiez le code ou demandez-en un nouveau.']],
            ], 422);
        }

        $sess = $request->session();
        $role = $sess->get('register.role', 'client');
        $step2 = $sess->get('register.step2', []);
        $step3 = $sess->get('register.step3', []);
        $step4 = $sess->get('register.step4', []);
        $step5 = $sess->get('register.step5', []);
        $step6 = $sess->get('register.step6', []);

        // Final complete check: every required step, in order, for this role.
        $incomplete = $this->progress->incompleteSteps($request, (string) $role);

        if ($incomplete !== []) {
            $first = $incomplete[0];

            return response()->json([
                'errors' => [
                    'finalize' => [$this->progress->blockedMessage($first)],
                ],
                'blocked_step' => $first['step'],
                'missing_fields' => $first['missing'],
                'incomplete_steps' => $incomplete,
            ], 422);
        }

        if (empty($step6) || empty($step6['email']) || empty($step6['password'])) {
            return response()->json([
                'errors' => ['finalize' => ['Les informations de sécurité sont incomplètes. Veuillez reprendre à l\'étape 6.']],
            ], 422);
        }

        // Ensure email uniqueness before creating
        if (User::where('email', $step6['email'])->exists()) {
            return response()->json([
                'errors' => ['email' => ['Cette adresse e-mail est déjà associée à un compte AgroNextZone.']],
            ], 422);
        }

        $fullName = trim(($step2['first_name'] ?? '') . ' ' . ($step2['last_name'] ?? ''));
        $name = $fullName !== '' ? $fullName : explode('@', $step6['email'])[0];

        try {
            DB::beginTransaction();

            // 1. Create User
            $user = User::create([
                'name' => $name,
                'email' => $step6['email'],
                'password' => Hash::make($step6['password']),
                'role' => $role,
                'phone' => $step2['phone'] ?? null,
                'avatar' => $step2['profile_photo_path'] ?? null,
                'bio' => $step4['description'] ?? null,
                'is_verified' => $role === 'client', // Client is verified upon OTP; Producer is pending CNI verification
            ]);

            $user->profile()->create([
                'first_name' => $step2['first_name'] ?? null,
                'last_name' => $step2['last_name'] ?? null,
                'gender' => $step2['gender'] ?? null,
                'date_of_birth' => $step2['date_of_birth'] ?? null,
            ]);

            // 2. Create Location if step 3 provided
            if (!empty($step3['region']) || !empty($step3['city'])) {
                DB::table('locations')->insert([
                    'user_id' => $user->id,
                    'country' => $step3['country'] ?? 'Cameroun',
                    'region' => $step3['region'] ?? '',
                    'city' => $step3['city'] ?? '',
                    'locality' => $step3['locality'] ?? null,
                    'address' => $step3['farm_location'] ?? null,
                    'latitude' => !empty($step3['latitude']) ? (float) $step3['latitude'] : null,
                    'longitude' => !empty($step3['longitude']) ? (float) $step3['longitude'] : null,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($role === 'producer') {
                $user->producerProfile()->create([
                    'activity_type' => $step4['activity_type'],
                    'specialty' => $step4['specialty'] ?? null,
                    'main_products' => $step4['main_products'] ?? null,
                    'farm_name' => $step4['farm_name'] ?? null,
                    'years_experience' => $step4['years_experience'] ?? null,
                    'description' => $step4['description'] ?? null,
                ]);

                $user->producerVerification()->create([
                    'cni_number' => $step5['cni_number'],
                    'document_path' => $step5['cni_path'],
                    'document_original_name' => $step5['cni_file_name'] ?? null,
                    'status' => ProducerVerification::STATUS_PENDING,
                    'submitted_at' => now(),
                ]);
            }

            DB::commit();

            // 3. Authenticate User
            Auth::login($user);

            // 4. Clear temporary registration session
            $sess->forget('register');

            $redirectUrl = $user->role === 'producer'
                ? route('producer.dashboard')
                : route('client.dashboard');

            return response()->json([
                'status' => 'ok',
                'user_id' => $user->id,
                'role' => $user->role,
                'redirect' => $redirectUrl,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'errors' => ['server' => ['Une erreur est survenue lors de l\'enregistrement : ' . $e->getMessage()]],
            ], 500);
        }
    }

    /**
     * Async check whether an email is already taken.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $email = trim((string) $request->input('email', ''));

        if ($email === '') {
            return response()->json(['available' => false, 'message' => 'Adresse e-mail vide.'], 422);
        }

        $exists = User::where('email', $email)->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'message' => 'Cet e-mail est déjà utilisé. Connectez-vous ou réinitialisez votre mot de passe.',
            ], 200);
        }

        return response()->json([
            'available' => true,
            'message' => 'Adresse e-mail disponible.',
        ], 200);
    }

    /**
     * Async check whether a phone is already taken.
     */
    public function checkPhone(Request $request): JsonResponse
    {
        $phone = trim((string) $request->input('phone', ''));

        if ($phone === '') {
            $phone = (string) $request->session()->get('register.step2.phone', '');
        }

        if ($phone === '') {
            return response()->json(['available' => false, 'message' => 'Numéro de téléphone non renseigné.'], 422);
        }

        $exists = User::where('phone', $phone)->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'message' => 'Ce numéro de téléphone est déjà associé à un compte.',
            ], 200);
        }

        return response()->json([
            'available' => true,
            'message' => 'Numéro de téléphone disponible.',
        ], 200);
    }

    /**
     * Return stored session data for Step 7 recap.
     */
    public function getRecap(Request $request): JsonResponse
    {
        // The recap and the OTP sender both read this same server-side payload.
        // CNI data is deliberately excluded: it is private and never displayed.
        return response()->json($this->progress->summary($request));
    }
}
