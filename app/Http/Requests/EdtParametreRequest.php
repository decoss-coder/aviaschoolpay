<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EdtParametreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'annee_scolaire_id' => ['nullable', 'integer', 'exists:annees_scolaires,id'],
            'policy_id' => ['nullable', 'integer', 'exists:edt_policies,id'],
            'mode_generation_defaut' => ['required', 'in:strict_officiel,prive_equilibre,prive_contraint,provisoire_vacataires'],

            'jours_autorises_json' => ['nullable', 'array'],
            'jours_autorises_json.*' => ['string', 'in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche'],

            'creneaux_autorises_json' => ['nullable', 'array'],
            'creneaux_autorises_json.*' => ['integer', 'exists:creneaux,id'],

            'salles_autorisees_json' => ['nullable', 'array'],
            'salles_autorisees_json.*' => ['integer', 'exists:salles,id'],

            'attendre_horaires_vacataires' => ['nullable', 'boolean'],
            'bloquer_si_vacataire_sans_horaire' => ['nullable', 'boolean'],
            'respecter_imports_vacataires' => ['nullable', 'boolean'],
            'regrouper_heures_vacataires' => ['nullable', 'boolean'],

            'autoriser_reduction_heures' => ['nullable', 'boolean'],
            'max_reduction_minutes_par_classe' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'max_reduction_minutes_par_matiere' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'autoriser_matieres_facultatives' => ['nullable', 'boolean'],

            'prioriser_classes_examen' => ['nullable', 'boolean'],
            'prioriser_permanents' => ['nullable', 'boolean'],
            'equilibrer_journees_classes' => ['nullable', 'boolean'],
            'equilibrer_journees_profs' => ['nullable', 'boolean'],

            'respecter_tp_consecutifs' => ['nullable', 'boolean'],
            'eviter_eps_heures_chaudes' => ['nullable', 'boolean'],
            'limiter_niveaux_prof' => ['nullable', 'boolean'],
            'max_niveaux_par_prof' => ['nullable', 'integer', 'min:1', 'max:10'],
            'limiter_heures_creuses' => ['nullable', 'boolean'],
            'max_heures_creuses_prof' => ['nullable', 'integer', 'min:0', 'max:10'],

            'autoriser_trous' => ['nullable', 'boolean'],
            'tolerer_surcharge_legere' => ['nullable', 'boolean'],

            'activer_apprentissage_ajustements' => ['nullable', 'boolean'],
            'verrouiller_ajustements_manuels_par_defaut' => ['nullable', 'boolean'],

            'notes_generation' => ['nullable', 'string', 'max:5000'],
            'actif' => ['nullable', 'boolean'],
        ];
    }
}