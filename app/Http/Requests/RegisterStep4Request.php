<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStep4Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_type' => ['required', 'string', 'max:120'],
            'specialty' => ['nullable', 'string', 'max:200'],
            'main_products' => ['nullable', 'string', 'max:255'],
            'farm_name' => ['nullable', 'string', 'max:150'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'activity_type.required' => 'Veuillez préciser votre type d’activité agricole.',
            'activity_type.max' => 'Le type d’activité ne doit pas dépasser 120 caractères.',
            'farm_name.max' => 'Le nom de l’exploitation ne doit pas dépasser 150 caractères.',
            'years_experience.integer' => 'Les années d’expérience doivent être un nombre entier.',
            'years_experience.min' => 'Les années d’expérience ne peuvent pas être négatives.',
            'description.max' => 'La description ne doit pas dépasser 1000 caractères.',
        ];
    }
}
