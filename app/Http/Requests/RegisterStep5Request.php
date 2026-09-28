<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStep5Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isProducer = session()->get('register.role') === 'producer';
        $hasExistingDoc = session()->has('register.step5.cni_path');

        return [
            'cni_number' => ['required', 'string', 'max:50'],
            'cni_document' => [$isProducer && ! $hasExistingDoc ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'cni_number.required' => 'Le numéro de la pièce d’identité (CNI) est obligatoire.',
            'cni_number.max' => 'Le numéro de CNI ne doit pas dépasser 50 caractères.',
            'cni_document.required' => 'Veuillez téléverser une copie ou une photo de votre pièce d’identité.',
            'cni_document.file' => 'Le fichier téléversé n’est pas valide.',
            'cni_document.mimes' => 'Le document doit être une image (JPG, PNG) ou un document PDF.',
            'cni_document.max' => 'Le document ne doit pas dépasser 5 Mo.',
        ];
    }
}
