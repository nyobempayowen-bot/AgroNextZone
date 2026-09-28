<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class RegisterStep2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $minAge = 16; // minimum age for registration (adjust if needed)
        $dateLimit = Carbon::now()->subYears($minAge)->format('Y-m-d');

        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['required', 'date', 'before_or_equal:' . $dateLimit],
            'phone' => ['required', 'string', 'regex:/^[0-9+\s-]{8,20}$/'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'gender.required' => 'Veuillez sélectionner votre sexe.',
            'gender.in' => 'Le choix du sexe est invalide.',
            'date_of_birth.required' => 'La date de naissance est obligatoire.',
            'date_of_birth.date' => 'La date de naissance n’est pas valide.',
            'date_of_birth.before_or_equal' => 'Vous devez avoir au moins 16 ans pour vous inscrire.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.regex' => 'Le numéro de téléphone n’est pas valide (ex: +237 655 00 11 22).',
            'profile_photo.image' => 'La photo de profil doit être une image valide.',
            'profile_photo.mimes' => 'La photo doit être au format JPG, JPEG, PNG ou WEBP.',
            'profile_photo.max' => 'La photo de profil ne doit pas dépasser 2 Mo.',
        ];
    }
}
