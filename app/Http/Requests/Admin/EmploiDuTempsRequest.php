<?php

namespace App\Http\Requests\Admin;

use App\Models\EmploiDuTemps;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmploiDuTempsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['enseignant_id', 'salle_id'] as $field) {
            $value = $this->input($field);

            if ($value === '' || $value === '0' || $value === 0) {
                $this->merge([$field => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'annee_scolaire_id' => ['required', 'integer'],
            'jour' => ['required', 'string', Rule::in(EmploiDuTemps::jours())],
            'classe_id' => ['required', 'integer'],
            'matiere_id' => ['required', 'integer'],
            'enseignant_id' => ['nullable', 'integer'],
            'salle_id' => ['nullable', 'integer'],
            'creneau_id' => ['required', 'integer'],
            'valide_du' => ['nullable', 'date'],
            'valide_au' => ['nullable', 'date', 'after_or_equal:valide_du'],
            'actif' => ['nullable', 'boolean'],
            'lock_for_future' => ['nullable', 'boolean'],
            'adjustment_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
