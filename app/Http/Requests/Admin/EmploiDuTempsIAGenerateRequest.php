<?php

namespace App\Http\Requests\Admin;

use App\Models\EmploiDuTemps;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmploiDuTempsIAGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'annee_scolaire_id' => ['required', 'integer'],
            'classe_id' => ['nullable', 'integer'],
            'jours' => ['nullable', 'array'],
            'jours.*' => ['string', Rule::in(EmploiDuTemps::jours())],
            'salle_ids' => ['nullable', 'array'],
            'salle_ids.*' => ['integer'],
            'creneau_ids' => ['nullable', 'array'],
            'creneau_ids.*' => ['integer'],
            'strategie' => ['nullable', 'string', Rule::in([
                'equilibree',
                'compacte',
                'matieres_principales',
                'disponibilite_enseignants',
            ])],
            'autoriser_trous' => ['nullable', 'boolean'],
            'tolerer_surcharge' => ['nullable', 'boolean'],
            'valide_du' => ['nullable', 'date'],
            'valide_au' => ['nullable', 'date', 'after_or_equal:valide_du'],
        ];
    }
}