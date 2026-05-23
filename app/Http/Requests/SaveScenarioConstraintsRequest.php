<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveScenarioConstraintsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'constraints' => ['required', 'array'],
            'constraints.*.constraint_id' => ['required', 'integer', 'exists:edt_constraint_catalog,id'],
            'constraints.*.enabled' => ['required', 'boolean'],
            'constraints.*.weight' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'constraints.*.params_json' => ['nullable', 'array'],
        ];
    }
}