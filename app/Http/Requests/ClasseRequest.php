<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClasseRequest extends FormRequest
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
        $user = $this->user();
        $etabId = $user?->etablissement_id;

        $classeRoute = $this->route('classe');
        $classeId = is_object($classeRoute) ? $classeRoute->id : $classeRoute;

        $anneeId = $this->input('annee_scolaire_id');

        return [
            'annee_scolaire_id' => [
                'required',
                'integer',
                Rule::exists('annees_scolaires', 'id')->where(fn ($q) => $q->where('etablissement_id', $etabId)),
            ],

            'niveau_id' => [
                'required',
                'integer',
                Rule::exists('niveaux', 'id')->where(fn ($q) => $q->where('etablissement_id', $etabId)),
            ],

            'serie_id' => [
                'nullable',
                'integer',
                Rule::exists('series', 'id'),
            ],

            'nom' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classes', 'nom')
                    ->where(fn ($q) => $q
                        ->where('etablissement_id', $etabId)
                        ->where('annee_scolaire_id', $anneeId)
                        ->where('niveau_id', $this->input('niveau_id'))
                    )
                    ->ignore($classeId),
            ],

            'capacite' => [
                'required',
                'integer',
                'min:1',
                'max:200',
            ],

            'scolarite_annuelle' => [
                'required',
                'integer',
                'min:0',
                'max:10000000',
            ],

            'frais_inscription' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000',
            ],

            'frais_reinscription' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'professeur_principal_id' => [
                'nullable',
                'integer',
                Rule::exists('enseignants', 'id')->where(fn ($q) => $q->where('etablissement_id', $etabId)),
            ],

            'active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'annee_scolaire_id.required' => 'Vous devez sélectionner une année scolaire.',
            'annee_scolaire_id.exists' => 'L’année scolaire choisie est invalide.',

            'niveau_id.required' => 'Vous devez sélectionner un niveau.',
            'niveau_id.exists' => 'Le niveau choisi n’existe pas dans votre établissement.',

            'serie_id.exists' => 'La série choisie est invalide.',

            'nom.required' => 'Le nom de la classe est obligatoire.',
            'nom.unique' => 'Une classe avec ce nom existe déjà pour ce niveau et cette année scolaire.',
            'nom.max' => 'Le nom ne peut dépasser 50 caractères.',

            'capacite.required' => 'Vous devez indiquer la capacité maximale.',
            'capacite.min' => 'La capacité doit être d’au moins 1 élève.',
            'capacite.max' => 'La capacité ne peut dépasser 200 élèves.',

            'scolarite_annuelle.required' => 'La scolarité annuelle est obligatoire.',
            'scolarite_annuelle.min' => 'La scolarité ne peut être négative.',
            'scolarite_annuelle.max' => 'Montant trop élevé (maximum 10 000 000 FCFA).',

            'frais_inscription.min' => 'Les frais d’inscription ne peuvent être négatifs.',
            'frais_inscription.max' => 'Les frais d’inscription ne peuvent dépasser 1 000 000 FCFA.',

            'frais_reinscription.min' => 'Les frais de réinscription ne peuvent être négatifs.',
            'frais_reinscription.max' => 'Les frais de réinscription ne peuvent dépasser 1 000 000 FCFA.',

            'professeur_principal_id.exists' => 'L’enseignant choisi n’existe pas dans votre établissement.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'etablissement_id' => $this->user()?->etablissement_id,
            'active' => $this->boolean('active', true),

            'scolarite_annuelle' => $this->input('scolarite_annuelle', 0) === '' ? 0 : $this->input('scolarite_annuelle', 0),
            'frais_inscription' => $this->input('frais_inscription', 0) === '' ? 0 : $this->input('frais_inscription', 0),
            'frais_reinscription' => $this->input('frais_reinscription', 0) === '' ? 0 : $this->input('frais_reinscription', 0),

            'nom' => trim((string) $this->input('nom')),
            'description' => $this->input('description') !== null
                ? trim((string) $this->input('description'))
                : null,
        ]);
    }
}