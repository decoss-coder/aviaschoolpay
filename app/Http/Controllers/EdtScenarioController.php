<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\EdtConstraintCatalog;
use App\Models\EdtGenerationScenario;
use App\Models\EdtPolicy;
use App\Models\EdtVacataireImport;
use App\Models\Enseignant;
use App\Models\Salle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EdtScenarioController extends Controller
{
    public function index(Request $request): View
    {
        $etabId = $request->user()->etablissement_id;

        return view('emploi-du-temps.assistant.index', [
            'annees' => AnneeScolaire::query()
                ->where('etablissement_id', $etabId)
                ->orderByDesc('date_debut')
                ->get(),

            'policies' => class_exists(EdtPolicy::class)
                ? EdtPolicy::query()
                    ->where('etablissement_id', $etabId)
                    ->where('actif', true)
                    ->orderBy('nom')
                    ->get()
                : collect(),

            'classes' => Classe::query()
                ->where('etablissement_id', $etabId)
                ->where('active', true)
                ->orderBy('nom')
                ->get(),

            'enseignants' => Enseignant::query()
                ->where('etablissement_id', $etabId)
                ->where('actif', true)
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get(),

            'salles' => Salle::query()
                ->where('etablissement_id', $etabId)
                ->where('active', true)
                ->orderBy('nom')
                ->get(),

            'creneaux' => Creneau::query()
                ->where('etablissement_id', $etabId)
                ->orderBy('ordre')
                ->get(),

            'constraints' => EdtConstraintCatalog::query()
                ->orderBy('categorie')
                ->orderBy('libelle')
                ->get(),

            'recentScenarios' => EdtGenerationScenario::query()
                ->where('etablissement_id', $etabId)
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $etabId = $request->user()->etablissement_id;

        $validated = $request->validate([
            'annee_scolaire_id' => ['required', 'integer', 'exists:annees_scolaires,id'],
            'policy_id' => ['nullable', 'integer', 'exists:edt_policies,id'],
            'nom' => ['required', 'string', 'max:150'],
            'mode_generation' => ['required', 'in:strict_officiel,prive_equilibre,prive_contraint,provisoire_vacataires'],
            'portee' => ['required', 'in:globale,classes_selectionnees,enseignants_selectionnes'],
            'jours_json' => ['nullable', 'array'],
            'jours_json.*' => ['string'],
            'creneaux_json' => ['nullable', 'array'],
            'creneaux_json.*' => ['integer'],
            'salles_json' => ['nullable', 'array'],
            'salles_json.*' => ['integer'],
            'options_json' => ['nullable', 'array'],
            'scope_classes' => ['nullable', 'array'],
            'scope_classes.*' => ['integer'],
            'scope_enseignants' => ['nullable', 'array'],
            'scope_enseignants.*' => ['integer'],
        ]);

        $annee = AnneeScolaire::query()
            ->where('etablissement_id', $etabId)
            ->findOrFail((int) $validated['annee_scolaire_id']);

        $policyId = null;
        if (!empty($validated['policy_id'])) {
            $policy = EdtPolicy::query()
                ->where('etablissement_id', $etabId)
                ->findOrFail((int) $validated['policy_id']);

            $policyId = $policy->id;
        }

        $scenario = EdtGenerationScenario::create([
            'etablissement_id' => $etabId,
            'annee_scolaire_id' => $annee->id,
            'policy_id' => $policyId,
            'nom' => $validated['nom'],
            'mode_generation' => $validated['mode_generation'],
            'portee' => $validated['portee'],
            'jours_json' => $validated['jours_json'] ?? [],
            'creneaux_json' => $validated['creneaux_json'] ?? [],
            'salles_json' => $validated['salles_json'] ?? [],
            'options_json' => $validated['options_json'] ?? [],
            'created_by' => $request->user()->id,
        ]);

        $this->syncScopes($scenario, $etabId, $validated);
        $this->seedDefaultConstraints($scenario);

        return redirect()
            ->route('emploi-du-temps.assistant.scenarios.show', $scenario)
            ->with('success', 'Scénario créé avec succès.');
    }

    public function show(Request $request, EdtGenerationScenario $scenario): View
    {
        $this->authorizeScenario($request, $scenario);

        $constraints = DB::table('edt_generation_scenario_constraints as sc')
            ->join('edt_constraint_catalog as c', 'c.id', '=', 'sc.constraint_id')
            ->where('sc.scenario_id', $scenario->id)
            ->orderBy('c.categorie')
            ->orderBy('c.libelle')
            ->select([
                'sc.id',
                'sc.scenario_id',
                'sc.constraint_id',
                'sc.enabled',
                'sc.weight',
                'sc.params_json',
                'c.code',
                'c.libelle',
                'c.description',
                'c.categorie',
                'c.is_mandatory',
            ])
            ->get();

        $scopes = DB::table('edt_generation_scenario_scopes')
            ->where('scenario_id', $scenario->id)
            ->orderBy('scope_type')
            ->orderBy('scope_id')
            ->get();

        $runs = DB::table('edt_generation_runs')
            ->where('scenario_id', $scenario->id)
            ->latest('id')
            ->limit(20)
            ->get();

        $imports = EdtVacataireImport::query()
            ->where('etablissement_id', $scenario->etablissement_id)
            ->where('annee_scolaire_id', $scenario->annee_scolaire_id)
            ->latest('id')
            ->limit(20)
            ->get();

        return view('emploi-du-temps.assistant.show', [
            'scenario' => $scenario,
            'constraints' => $constraints,
            'scopes' => $scopes,
            'runs' => $runs,
            'imports' => $imports,
        ]);
    }

    private function authorizeScenario(Request $request, EdtGenerationScenario $scenario): void
    {
        abort_unless($scenario->etablissement_id === $request->user()->etablissement_id, 403);
    }

    private function syncScopes(EdtGenerationScenario $scenario, int $etabId, array $validated): void
    {
        DB::table('edt_generation_scenario_scopes')
            ->where('scenario_id', $scenario->id)
            ->delete();

        if (($validated['portee'] ?? 'globale') === 'classes_selectionnees') {
            $classIds = Classe::query()
                ->where('etablissement_id', $etabId)
                ->whereIn('id', $validated['scope_classes'] ?? [])
                ->pluck('id');

            foreach ($classIds as $classeId) {
                DB::table('edt_generation_scenario_scopes')->insert([
                    'scenario_id' => $scenario->id,
                    'scope_type' => 'classe',
                    'scope_id' => $classeId,
                ]);
            }
        }

        if (($validated['portee'] ?? 'globale') === 'enseignants_selectionnes') {
            $enseignantIds = Enseignant::query()
                ->where('etablissement_id', $etabId)
                ->whereIn('id', $validated['scope_enseignants'] ?? [])
                ->pluck('id');

            foreach ($enseignantIds as $enseignantId) {
                DB::table('edt_generation_scenario_scopes')->insert([
                    'scenario_id' => $scenario->id,
                    'scope_type' => 'enseignant',
                    'scope_id' => $enseignantId,
                ]);
            }
        }
    }

    private function seedDefaultConstraints(EdtGenerationScenario $scenario): void
    {
        $catalog = EdtConstraintCatalog::query()->get();

        foreach ($catalog as $constraint) {
            DB::table('edt_generation_scenario_constraints')->updateOrInsert(
                [
                    'scenario_id' => $scenario->id,
                    'constraint_id' => $constraint->id,
                ],
                [
                    'enabled' => $constraint->is_mandatory ? 1 : (int) $constraint->default_enabled,
                    'weight' => $constraint->default_weight ?? 100,
                    'params_json' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}