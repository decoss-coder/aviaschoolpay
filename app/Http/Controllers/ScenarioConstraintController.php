<?php

namespace App\Http\Controllers;

use App\Models\EdtConstraintCatalog;
use App\Models\EdtGenerationScenario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScenarioConstraintController extends Controller
{
    public function save(Request $request, EdtGenerationScenario $scenario): RedirectResponse
    {
        $this->authorizeScenario($request, $scenario);

        $validated = $request->validate([
            'constraints' => ['required', 'array'],
            'constraints.*.constraint_id' => ['required', 'integer', 'exists:edt_constraint_catalog,id'],
            'constraints.*.enabled' => ['required', 'boolean'],
            'constraints.*.weight' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'constraints.*.params_json' => ['nullable', 'array'],
        ]);

        $catalog = EdtConstraintCatalog::query()
            ->whereIn('id', collect($validated['constraints'])->pluck('constraint_id'))
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($scenario, $validated, $catalog) {
            foreach ($validated['constraints'] as $row) {
                $constraint = $catalog->get((int) $row['constraint_id']);
                if (!$constraint) {
                    continue;
                }

                $enabled = $constraint->is_mandatory
                    ? 1
                    : (int) $row['enabled'];

                DB::table('edt_generation_scenario_constraints')->updateOrInsert(
                    [
                        'scenario_id' => $scenario->id,
                        'constraint_id' => $constraint->id,
                    ],
                    [
                        'enabled' => $enabled,
                        'weight' => $row['weight'] ?? $constraint->default_weight ?? 100,
                        'params_json' => !empty($row['params_json']) ? json_encode($row['params_json']) : null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $mandatoryConstraints = EdtConstraintCatalog::query()
                ->where('is_mandatory', true)
                ->get();

            foreach ($mandatoryConstraints as $constraint) {
                DB::table('edt_generation_scenario_constraints')->updateOrInsert(
                    [
                        'scenario_id' => $scenario->id,
                        'constraint_id' => $constraint->id,
                    ],
                    [
                        'enabled' => 1,
                        'weight' => $constraint->default_weight ?? 1000,
                        'params_json' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        return back()->with('success', 'Contraintes du scénario mises à jour.');
    }

    private function authorizeScenario(Request $request, EdtGenerationScenario $scenario): void
    {
        abort_unless($scenario->etablissement_id === $request->user()->etablissement_id, 403);
    }
}