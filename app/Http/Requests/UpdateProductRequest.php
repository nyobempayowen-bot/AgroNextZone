<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Bloc B — IDOR defense: the ownership check happens BEFORE any
        // validation runs, so a producer probing another producer's offer
        // gets a clean 403 regardless of the payload.
        $product = $this->route('slug') !== null
            ? \App\Models\Product::where('slug', (string) $this->route('slug'))->first()
            : null;

        if ($product === null) {
            return true; // 404 handled later by firstOrFail in the controller.
        }

        return $this->user() !== null
            && $this->user()->can('update', $product);
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

