<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Single server-side source of truth for the step-by-step registration.
 *
 * Guarantees:
 * - a step can only be completed if every previous required step is stored;
 * - a step's required fields must be present and valid before anything is saved;
 * - the recap and the OTP send read the SAME server-side value for the email;
 * - the client and producer tracks require different fields.
 *
 * The server never trusts the browser: session order is authoritative.
 */
class RegisterProgress
{
    /**
     * Ordered step definitions per role.
     * Keys are session keys, values are the required field names inside them.
     */
    public const CLIENT_STEPS = [
        1 => 'role',
        2 => 'step2',
        3 => 'step3',
        6 => 'step6',
    ];

    public const PRODUCER_STEPS = [
        1 => 'role',
        2 => 'step2',
        3 => 'step3',
        4 => 'step4',
        5 => 'step5',
        6 => 'step6',
    ];

    public const LABELS = [
        1 => 'Profil (Client ou Producteur)',
        2 => 'Identité & contact',
        3 => 'Localisation',
        4 => 'Activité professionnelle',
        5 => 'Pièce d\'identité (CNI)',
        6 => 'Sécurité & conditions',
    ];

    /**
     * Required keys inside each session payload.
     * Only fields the interface presents as mandatory are listed.
     */
    private const REQUIRED_FIELDS = [
        2 => ['first_name', 'last_name', 'gender', 'date_of_birth', 'phone'],
        3 => ['country', 'region', 'city'],
        4 => ['activity_type'],
        // step5 requires both the CNI number and the uploaded private document
        5 => ['cni_number', 'cni_path'],
        6 => ['email', 'password', 'accepted_terms'],
    ];

    /**
     * Steps required for the given role, in order.
     *
     * @return array<int, string>
     */
    public function requiredSteps(string $role): array
    {
        return $role === 'producer' ? self::PRODUCER_STEPS : self::CLIENT_STEPS;
    }

    /**
     * The step following the given one for the role, or null when finished.
     */
    public function nextStep(string $role, int $step): ?int
    {
        $steps = array_values($this->requiredSteps($role));
        $index = array_search($step, $steps, true);

        if ($index === false) {
            return null;
        }

        return $steps[$index + 1] ?? null;
    }

    /**
     * Missing required session data for one step, as human readable labels.
     *
     * @return array<int, string>
     */
    public function missingFieldsForStep(Request $request, string $role, int $step): array
    {
        $steps = $this->requiredSteps($role);

        if (! array_key_exists($step, $steps)) {
            return [];
        }

        $key = $steps[$step];

        if ($key === 'role') {
            return $role === '' ? ['role'] : [];
        }

        $payload = $request->session()->get('register.' . $key, []);

        if (! is_array($payload)) {
            return self::REQUIRED_FIELDS[$step] ?? [];
        }

        $missing = [];

        foreach (self::REQUIRED_FIELDS[$step] ?? [] as $field) {
            $value = $payload[$field] ?? null;

            // accepted_terms must be strictly true: false and missing are both invalid.
            if ($field === 'accepted_terms') {
                if ($value !== true && $value !== 1 && $value !== '1') {
                    $missing[] = $field;
                }
                continue;
            }

            if ($value === null || $value === '' || (is_array($value) && $value === [])) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Every step that still needs to be completed, in order, before finalisation.
     *
     * @return array<int, array{step:int,label:string,missing:array<int,string>}>
     */
    public function incompleteSteps(Request $request, string $role): array
    {
        $incomplete = [];

        foreach ($this->requiredSteps($role) as $step => $key) {
            $missing = $this->missingFieldsForStep($request, $role, $step);

            if ($missing !== []) {
                $incomplete[] = [
                    'step' => $step,
                    'label' => self::LABELS[$step] ?? 'Étape ' . $step,
                    'missing' => $missing,
                ];
            }
        }

        return $incomplete;
    }

    /**
     * Whether the given step may be reached: every previous step must be complete.
     * Returns the first blocking step, or null when allowed.
     */
    public function firstBlockingStepBefore(Request $request, string $role, int $step): ?array
    {
        foreach ($this->incompleteSteps($request, $role) as $incomplete) {
            if ($incomplete['step'] < $step) {
                return $incomplete;
            }
        }

        return null;
    }

    /**
     * Human readable message for a blocked progression.
     *
     * @param  array{step:int,label:string,missing:array<int,string>}  $blocking
     */
    public function blockedMessage(array $blocking): string
    {
        return 'Étape ' . $blocking['step'] . ' (« ' . $blocking['label'] . ' ») incomplète : '
            . $this->humanizeMissing($blocking['missing']) . '. Veuillez compléter cette étape.';
    }

    /**
     * @param  array<int, string>  $fields
     */
    public function humanizeMissing(array $fields): string
    {
        $labels = [
            'role' => 'choix du profil',
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'gender' => 'sexe',
            'date_of_birth' => 'date de naissance',
            'phone' => 'téléphone',
            'country' => 'pays',
            'region' => 'région',
            'city' => 'ville',
            'activity_type' => 'type d\'activité',
            'cni_number' => 'numéro de CNI',
            'cni_path' => 'document CNI',
            'email' => 'adresse e-mail',
            'password' => 'mot de passe',
            'accepted_terms' => 'acceptation des conditions de confidentialité',
        ];

        $names = array_map(fn (string $f) => $labels[$f] ?? $f, $fields);

        return implode(', ', $names);
    }

    /**
     * Reliable server-side email (recap + OTP always read this).
     */
    public function email(Request $request): string
    {
        return (string) $request->session()->get('register.step6.email', '');
    }

    /**
     * Consistent payload used by both the recap view and the OTP sender.
     *
     * @return array<string, mixed>
     */
    public function summary(Request $request): array
    {
        $session = $request->session();
        $role = (string) $session->get('register.role', 'client');

        return [
            'role' => $role,
            'step2' => $session->get('register.step2', []),
            'step3' => $session->get('register.step3', []),
            'step4' => $session->get('register.step4', []),
            // step5 is intentionally reduced to non-sensitive fields: the CNI path
            // and file name must never reach the browser.
            'step5' => [
                'provided' => (bool) $session->get('register.step5.cni_path'),
                'cni_number_present' => (bool) $session->get('register.step5.cni_number'),
            ],
            'step6' => ['email' => $this->email($request)],
            'incomplete_steps' => $this->incompleteSteps($request, $role),
        ];
    }
}
