<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AlertePointageTraitementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [
            'super_admin',
            'directeur',
            'directeur_adjoint',
            'gestionnaire',
            'secretaire',
            'comptable',
            'censeur',
        ], true);
    }

    public function rules(): array
    {
        return [
            'commentaire_traitement' => ['nullable', 'string', 'max:1000'],
        ];
    }
}