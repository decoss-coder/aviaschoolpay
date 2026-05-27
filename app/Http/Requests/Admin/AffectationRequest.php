<?php

namespace App\Http\Requests\Admin;

use App\Models\Classe;
use App\Services\Scolarite\MatiereNiveauRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AffectationRequest extends FormRequest
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
            'enseignant_id' => ['required', 'integer', 'exists:enseignants,id'],
            'classe_id' => ['required', 'integer', 'exists:classes,id'],
            'matiere_id' => ['required', 'integer', 'exists:matieres,id'],
            'annee_scolaire_id' => ['required', 'integer', 'exists:annees_scolaires,id'],
            'volume_horaire_hebdo' => ['required', 'numeric', 'min:0.5', 'max:60'],
            'est_professeur_principal' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'est_professeur_principal' => $this->boolean('est_professeur_principal'),
            'active' => $this->has('active') ? $this->boolean('active') : true,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $matiereId = (int) $this->input('matiere_id');
            $classeId  = (int) $this->input('classe_id');
            if (! $matiereId || ! $classeId) {
                return;
            }
            if (! MatiereNiveauRules::estAutoriseeIds($matiereId, $classeId)) {
                $matiere = \App\Models\Matiere::find($matiereId);
                $classe  = Classe::with('niveau:id,code,cycle')->find($classeId);
                // Pour les sous-disciplines on remonte au parent pour avoir le message
                $code = $matiere?->parent_matiere_id
                    ? optional(\App\Models\Matiere::find($matiere->parent_matiere_id))->code
                    : $matiere?->code;
                $v->errors()->add(
                    'matiere_id',
                    MatiereNiveauRules::messageInterdit($code, $classe)
                );
            }
        });
    }
}