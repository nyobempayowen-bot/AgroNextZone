<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStep3Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'locality' => ['nullable', 'string', 'max:120'],
            // bornes GPS reelles : lat [-90, 90] et lon [-180, 180].
            // Le champ reste facultatif (l'utilisateur peut saisir sa ville
            // manuellement sans avoir clique sur « Utiliser ma position »).
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'farm_location' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'country.required' => 'Veuillez renseigner le pays.',
            'region.required' => 'Veuillez sélectionner une région.',
            'city.required' => 'Veuillez renseigner votre ville.',
            'latitude.numeric' => 'La latitude doit être un nombre décimal valide (ex : 3.8480).',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'longitude.numeric' => 'La longitude doit être un nombre décimal valide (ex : 11.5021).',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
            'farm_location.max' => 'La description de l\'exploitation ne doit pas dépasser 255 caractères.',
        ];
    }

    /**
     * Contrôle de cohérence : si l'utilisateur a cliqué sur « Utiliser ma
     * position », les deux coordonnées doivent arriver ensemble. Sans cela on
     * stocke une position au point 0,0 (Golfe de Guinée) invisible sur la carte.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lat = $this->input('latitude');
            $lng = $this->input('longitude');

            if (($lat === null || $lat === '') && ($lng === null || $lng === '')) {
                return; // aucune coordonnée : saisie manuelle, c'est valide
            }

            if ($lat === null || $lat === '' || $lng === null || $lng === '') {
                $validator->errors()->add('latitude', 'Latitude et longitude doivent être renseignées ensemble.');

                return;
            }

            // 0,0 n'est jamais une position valide au Cameroun.
            if ((float) $lat === 0.0 && (float) $lng === 0.0) {
                $validator->errors()->add('latitude', 'Position GPS non exploitable. Saisissez votre ville manuellement.');
            }
        });
    }
}
