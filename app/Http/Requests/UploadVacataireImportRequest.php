<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadVacataireImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'enseignant_id' => ['required', 'integer', 'exists:enseignants,id'],
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annees_scolaires,id'],
            'source_type' => ['required', 'in:photo,pdf,image,scan,manuel'],
            'fichier' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp'],
            'manual_slots' => ['nullable', 'array'],
        ];
    }
}