<?php

namespace App\Http\Controllers;

use App\Models\EdtGenerationRun;
use App\Models\EdtGenerationScenario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EdtGenerationController extends Controller
{
    public function generate(Request $request, EdtGenerationScenario $scenario): RedirectResponse
    {
        $this->authorizeScenario($request, $scenario);

        if (class_exists(\App\Services\Edt\GenerationPlanner::class)) {
            $run = app(\App\Services\Edt\GenerationPlanner::class)->generate($scenario, $request->user());

            return redirect()
                ->route('emploi-du-temps.assistant.runs.report', $run)
                ->with('success', 'Génération terminée.');
        }

        $run = EdtGenerationRun::create([
            'scenario_id' => $scenario->id,
            'etablissement_id' => $scenario->etablissement_id,
            'annee_scolaire_id' => $scenario->annee_scolaire_id,
            'run_uuid' => (string) Str::uuid(),
            'status' => 'failed',
            'score_global' => 0,
            'summary_json' => [
                'assignments_count' => 0,
                'issues_count' => 1,
                'message' => 'GenerationPlanner non branché.',
            ],
            'conformite_json' => [
                'score_global' => 0,
                'per_classe' => [],
                'issues_summary' => [
                    'errors' => 1,
                    'warnings' => 0,
                    'infos' => 0,
                ],
            ],
            'started_at' => now(),
            'finished_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        DB::table('edt_generation_issues')->insert([
            'run_id' => $run->id,
            'niveau' => 'error',
            'issue_code' => 'GENERATION_PLANNER_MISSING',
            'scope_type' => 'global',
            'scope_id' => null,
            'message' => 'Le service GenerationPlanner n’est pas encore branché.',
            'details_json' => json_encode(['scenario_id' => $scenario->id]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('emploi-du-temps.assistant.runs.report', $run)
            ->with('error', 'Le service de génération avancée n’est pas encore branché.');
    }

    public function report(Request $request, EdtGenerationRun $run): View
    {
        $this->authorizeRun($request, $run);

        $scenario = EdtGenerationScenario::query()->find($run->scenario_id);

        $issues = DB::table('edt_generation_issues')
            ->where('run_id', $run->id)
            ->orderByRaw("FIELD(niveau, 'error', 'warning', 'info')")
            ->orderByDesc('id')
            ->get();

        return view('emploi-du-temps.assistant.report', [
            'run' => $run,
            'scenario' => $scenario,
            'issues' => $issues,
        ]);
    }

    public function apply(Request $request, EdtGenerationRun $run): RedirectResponse
    {
        $this->authorizeRun($request, $run);

        if ($run->status === 'applied') {
            return back()->with('success', 'Cette génération a déjà été appliquée.');
        }

        if (class_exists(\App\Services\Edt\GenerationApplyService::class)) {
            app(\App\Services\Edt\GenerationApplyService::class)->apply($run, $request->user());

            return redirect()
                ->route('emploi-du-temps.grille', [
                    'annee_scolaire_id' => $run->annee_scolaire_id,
                ])
                ->with('success', 'Proposition appliquée à la grille.');
        }

        $summary = $this->decodeJsonField($run->summary_json);
        $assignments = $summary['assignments_payload'] ?? [];

        if (empty($assignments)) {
            return back()->with('error', 'Aucun payload d’affectation disponible à appliquer.');
        }

        DB::transaction(function () use ($run, $assignments, $request) {
            foreach ($assignments as $row) {
                DB::table('emploi_du_temps')->insert($this->buildEmploiPayload($run, $row, $request->user()->id));
            }

            $run->update([
                'status' => 'applied',
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('emploi-du-temps.grille', [
                'annee_scolaire_id' => $run->annee_scolaire_id,
            ])
            ->with('success', 'Proposition appliquée à la grille.');
    }

    private function buildEmploiPayload(EdtGenerationRun $run, array $row, int $userId): array
    {
        $columns = array_flip(Schema::getColumnListing('emploi_du_temps'));

        $payload = [
            'etablissement_id' => $run->etablissement_id,
            'annee_scolaire_id' => $run->annee_scolaire_id,
            'classe_id' => $row['classe_id'] ?? null,
            'matiere_id' => $row['matiere_id'] ?? null,
            'enseignant_id' => $row['enseignant_id'] ?? null,
            'salle_id' => $row['salle_id'] ?? null,
            'creneau_id' => $row['creneau_id'] ?? null,
            'jour' => $row['jour'] ?? null,
            'valide_du' => null,
            'valide_au' => null,
            'actif' => 1,
            'source' => 'ia',
            'generation_uuid' => $run->run_uuid,
            'locked_by_user' => 0,
            'ia_score' => $row['ia_score'] ?? null,
            'last_adjusted_by' => $userId,
            'last_adjusted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return array_intersect_key($payload, $columns);
    }

    private function decodeJsonField($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function authorizeScenario(Request $request, EdtGenerationScenario $scenario): void
    {
        abort_unless($scenario->etablissement_id === $request->user()->etablissement_id, 403);
    }

    private function authorizeRun(Request $request, EdtGenerationRun $run): void
    {
        abort_unless($run->etablissement_id === $request->user()->etablissement_id, 403);
    }
}