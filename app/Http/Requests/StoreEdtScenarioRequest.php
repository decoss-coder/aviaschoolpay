<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEdtScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'annee_scolaire_id' => ['required', 'integer', 'exists:annees_scolaires,id'],
            'policy_id' => ['nullable', 'integer', 'exists:edt_policies,id'],
            'nom' => ['required', 'string', 'max:150'],
            'mode_generation' => ['required', 'in:strict_officiel,prive_equilibre,prive_contraint,provisoire_vacataires'],
            'portee' => ['required', 'in:globale,classes_selectionnees,enseignants_selectionnes'],
            'jours_json' => ['nullable', 'array'],
            'creneaux_json' => ['nullable', 'array'],
            'salles_json' => ['nullable', 'array'],
            'options_json' => ['nullable', 'array'],
            'scope_classes' => ['nullable', 'array'],
            'scope_classes.*' => ['integer', 'exists:classes,id'],
            'scope_enseignants' => ['nullable', 'array'],
            'scope_enseignants.*' => ['integer', 'exists:enseignants,id'],
        ];
    }
}