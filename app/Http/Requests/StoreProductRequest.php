<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'producer';
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:200'],
            'categorie' => ['required', 'string', 'max:100'],
            'prix_num' => ['required', 'integer', 'min:100'],
            'unite' => ['required', 'string', 'max:50'],
            'stock' => ['required', 'integer', 'min:0'],
            'region' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1500'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}

