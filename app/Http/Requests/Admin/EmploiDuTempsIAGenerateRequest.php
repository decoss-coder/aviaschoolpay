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

            // Paramètres IA stockés dans edt_parametres et/ou envoyés par le formulaire.
            'policy_id' => ['nullable', 'integer'],
            'mode_generation_defaut' => ['nullable', 'string'],
            'attendre_horaires_vacataires' => ['nullable', 'boolean'],
            'bloquer_si_vacataire_sans_horaire' => ['nullable', 'boolean'],
            'respecter_imports_vacataires' => ['nullable', 'boolean'],
            'prioriser_classes_examen' => ['nullable', 'boolean'],
            'prioriser_permanents' => ['nullable', 'boolean'],
            'autoriser_reduction_heures' => ['nullable', 'boolean'],
            'max_reduction_minutes_par_classe' => ['nullable', 'integer', 'min:0'],
            'max_reduction_minutes_par_matiere' => ['nullable', 'integer', 'min:0'],
            'respecter_tp_consecutifs' => ['nullable', 'boolean'],
            'eviter_eps_heures_chaudes' => ['nullable', 'boolean'],
            'limiter_niveaux_prof' => ['nullable', 'boolean'],
            'max_niveaux_par_prof' => ['nullable', 'integer', 'min:1'],
            'limiter_heures_creuses' => ['nullable', 'boolean'],
            'max_heures_creuses_prof' => ['nullable', 'integer', 'min:0'],
            'autoriser_trous' => ['nullable', 'boolean'],
            'tolerer_surcharge' => ['nullable', 'boolean'],
            'tolerer_surcharge_legere' => ['nullable', 'boolean'],
            'notes_generation' => ['nullable', 'string'],

            'valide_du' => ['nullable', 'date'],
            'valide_au' => ['nullable', 'date', 'after_or_equal:valide_du'],
        ];
    }
}
