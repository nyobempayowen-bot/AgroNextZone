<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'client';
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'regex:/^[0-9+\s-]{8,20}$/'],
            'adresse' => ['required', 'string', 'max:255'],
            'moyen_paiement' => ['required', 'string', 'max:100'],
        ];
    }
}

