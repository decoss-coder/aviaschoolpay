<?php

namespace App\Services\Edt;

use App\Models\Affectation;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\EdtClassePlageHoraire;
use App\Models\EdtGenerationRun;
use App\Models\EdtGenerationScenario;
use App\Models\Enseignant;
use App\Models\Salle;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GenerationPlanner
{
    public function __construct(
        private readonly ReferentielService $referentielService,
        private readonly PrivatePolicyService $policyService,
        private readonly VacataireAvailabilityService $vacataireService,
        private readonly ExternalScheduleService $externalScheduleService,
        private readonly ConstraintEngine $constraintEngine,
        private readonly ConformityService $conformityService,
    ) {
    }

    public function generate(EdtGenerationScenario $scenario, User $user): EdtGenerationRun
    {
        $scenario->load(['policy', 'constraints.constraint', 'scopes']);

        $run = EdtGenerationRun::create([
            'scenario_id'       => $scenario->id,
            'etablissement_id'  => $scenario->etablissement_id,
            'annee_scolaire_id' => $scenario->annee_scolaire_id,
            'run_uuid'          => (string) Str::uuid(),
            'status'            => 'running',
            'started_at'        => now(),
            'created_by'        => $user->id,
        ]);

        $classes     = $this->resolveClasses($scenario);
        $enseignants = Enseignant::where('etablissement_id', $scenario->etablissement_id)->where('actif', true)->get();
        $salles      = Salle::where('etablissement_id', $scenario->etablissement_id)->where('active', true)->get();
        $creneaux    = Creneau::where('etablissement_id', $scenario->etablissement_id)->cours()->orderBy('ordre')->get();
        $constraints = $this->constraintEngine->resolveScenarioConstraints($scenario);

        $availability = $this->vacataireService->getAvailabilityMap($enseignants);
        $externalBusy = $this->externalScheduleService->getBusyMap($enseignants, $creneaux, $scenario->annee_scolaire_id);
        $creneauOrdreById = $creneaux->pluck('ordre', 'id')->all();
        $classePlageMap = EdtClassePlageHoraire::buildMap($classes->pluck('id'), $scenario->annee_scolaire_id);

        $affectationsMap = Affectation::where('annee_scolaire_id', $scenario->annee_scolaire_id)
            ->where('active', true)
            ->get()
            ->groupBy('matiere_id')
            ->map(fn ($byMatiere) => $byMatiere->groupBy('classe_id')->map(fn ($rows) => $rows->pluck('enseignant_id')->all()))
            ->all();

        $state = [
            'classes'          => [],
            'enseignants'      => [],
            'salles'           => [],
            'assignments'      => [],
            'classe_plage_map' => $classePlageMap,
        ];

        $issues = collect();
        $units  = collect();

        foreach ($classes as $classe) {
            $policy      = $this->policyService->resolvePolicyForClasse($classe, $scenario->policy);
            $demandUnits = $this->referentielService->buildDemandUnitsForClasse($classe, $policy);
            $demandUnits = $this->policyService->applyMatiereOverrides($demandUnits, $classe, $policy);

            foreach ($demandUnits as $unit) {
                $units->push($unit + ['classe' => $classe]);
            }
        }

        if ($classes->isEmpty()) {
            $issues->push($this->issue('NO_ACTIVE_CLASS', 'Aucune classe active trouvée pour cette génération.', null, null));
        }

        if ($creneaux->isEmpty()) {
            $issues->push($this->issue('NO_COURSE_SLOT', 'Aucun créneau de type cours n’est configuré. Ajoute les créneaux horaires avant de lancer l’IA.', null, null));
        }

        if ($enseignants->isEmpty()) {
            $issues->push($this->issue('NO_ACTIVE_TEACHER', 'Aucun enseignant actif trouvé.', null, null));
        }

        if ($units->isEmpty() && $classes->isNotEmpty()) {
            $issues->push($this->issue('NO_DEMAND_UNIT', 'Aucune unité de cours à placer. Vérifie les affectations enseignants/classes/disciplines ou le référentiel EDT.', null, null));
        }

        $units = $units->sortBy([
            fn ($row) => $row['ordre_montage'] ?? 999,
            fn ($row) => $row['classe']->edt_generation_priority ?? 5,
        ])->values();

        foreach ($units as $unit) {
            $candidates = $this->buildCandidates($unit, $enseignants, $salles, $creneaux, $availability, $externalBusy, $creneauOrdreById, $scenario, $classePlageMap, $affectationsMap);

            $valid = $candidates->filter(fn ($candidate) => $this->constraintEngine->allHardSatisfied($candidate, $unit, $state, $constraints));
            $best = $valid->sortByDesc(fn ($candidate) => $this->constraintEngine->score($candidate, $unit, $state, $constraints))->first();

            if (!$best) {
                $issues->push($this->issue('UNPLACED_UNIT', 'Impossible de placer une unité de cours.', $unit['classe_id'], $unit['matiere_id']));
                continue;
            }

            $state['classes'][$best['jour']][$best['creneau_id']][$unit['classe_id']] = true;

            if ($best['enseignant_id']) {
                $state['enseignants'][$best['jour']][$best['creneau_id']][$best['enseignant_id']] = true;
            }

            if ($best['salle_id']) {
                $state['salles'][$best['jour']][$best['creneau_id']][$best['salle_id']] = true;
            }

            $state['assignments'][] = [
                'classe_id'        => $unit['classe_id'],
                'classe_niveau_id' => $unit['classe']->niveau_id ?? null,
                'matiere_id'       => $unit['matiere_id'],
                'matiere_code'     => $unit['matiere_code'] ?? null,
                'enseignant_id'    => $best['enseignant_id'],
                'salle_id'         => $best['salle_id'],
                'creneau_id'       => $best['creneau_id'],
                'jour'             => $best['jour'],
                'source'           => 'ia',
                'ia_score'         => $this->constraintEngine->score($best, $unit, $state, $constraints),
            ];
        }

        $conformite = $this->conformityService->build($classes, collect($state['assignments']), $issues);

        $run->update([
            'status'       => 'completed',
            'score_global' => $conformite['score_global'] ?? 0,
            'summary_json' => [
                'assignments_count'   => count($state['assignments']),
                'issues_count'        => $issues->count(),
                'classes_count'       => $classes->count(),
                'teachers_count'      => $enseignants->count(),
                'rooms_count'         => $salles->count(),
                'course_slots_count'  => $creneaux->count(),
                'demand_units_count'  => $units->count(),
                'assignments_payload' => $state['assignments'],
            ],
            'conformite_json' => $conformite,
            'finished_at'     => now(),
        ]);

        foreach ($issues as $issue) {
            $run->issues()->create($issue);
        }

        return $run->fresh(['issues', 'scenario']);
    }

    private function issue(string $code, string $message, ?int $classeId, ?int $matiereId): array
    {
        return [
            'niveau' => 'warning',
            'issue_code' => $code,
            'scope_type' => $classeId ? 'classe' : 'global',
            'scope_id' => $classeId,
            'message' => $message,
            'details_json' => array_filter([
                'classe_id' => $classeId,
                'matiere_id' => $matiereId,
            ]),
        ];
    }

    private function resolveClasses(EdtGenerationScenario $scenario): Collection
    {
        $query = Classe::where('etablissement_id', $scenario->etablissement_id)->where('active', true);

        if ($scenario->portee === 'classes_selectionnees') {
            $ids = $scenario->scopes()->where('scope_type', 'classe')->pluck('scope_id');
            $query->whereIn('id', $ids);
        }

        return $query->with('niveau')->orderBy('nom')->get();
    }

    private function buildCandidates(
        array $unit,
        Collection $enseignants,
        Collection $salles,
        Collection $creneaux,
        array $availability,
        array $externalBusy,
        array $creneauOrdreById,
        EdtGenerationScenario $scenario,
        array $classePlageMap = [],
        array $affectationsMap = []
    ): Collection {
        $jours = $scenario->jours_json ?: ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
        $allowedCreneaux = collect($scenario->creneaux_json ?: [])->map(fn ($v) => (int) $v);
        $allowedSalles = collect($scenario->salles_json ?: [])->map(fn ($v) => (int) $v);

        $eligibleIds = $affectationsMap[$unit['matiere_id']][$unit['classe_id']] ?? null;
        $enseignantsCandidats = $eligibleIds !== null ? $enseignants->whereIn('id', $eligibleIds)->values() : $enseignants;

        $candidates = collect();

        foreach ($jours as $jour) {
            foreach ($creneaux as $creneau) {
                if ($allowedCreneaux->isNotEmpty() && !$allowedCreneaux->contains((int) $creneau->id)) {
                    continue;
                }

                if (!empty($classePlageMap)) {
                    $crenauPlage = $creneau->plage;
                    if (!EdtClassePlageHoraire::isAllowed($classePlageMap, $unit['classe_id'], $jour, $crenauPlage)) {
                        continue;
                    }
                }

                foreach ($enseignantsCandidats as $enseignant) {
                    $vacataireForbidden = collect($availability[$enseignant->id] ?? [])->contains(fn ($slot) => $slot['jour'] === $jour && (!empty($slot['creneau_id']) && (int) $slot['creneau_id'] === (int) $creneau->id) && in_array($slot['etat'], ['indisponible', 'a_eviter'], true));
                    $vacatairePreferred = collect($availability[$enseignant->id] ?? [])->contains(fn ($slot) => $slot['jour'] === $jour && (!empty($slot['creneau_id']) && (int) $slot['creneau_id'] === (int) $creneau->id) && $slot['etat'] === 'prefere');
                    $externalBusyFlag = $externalBusy[$enseignant->id][$jour][$creneau->id] ?? false;

                    $sallesList = $salles->isEmpty() ? collect([null]) : $salles->filter(fn ($s) => $allowedSalles->isEmpty() || $allowedSalles->contains((int) $s->id));
                    if ($sallesList->isEmpty()) {
                        $sallesList = collect([null]);
                    }

                    foreach ($sallesList as $salle) {
                        $candidates->push([
                            'jour' => $jour,
                            'creneau_id' => $creneau->id,
                            'creneau_ordre' => $creneau->ordre,
                            'creneau_plage' => $creneau->plage,
                            'creneau_ordre_by_id' => $creneauOrdreById,
                            'enseignant_id' => $enseignant->id,
                            'salle_id' => $salle?->id ?? null,
                            'vacataire_forbidden' => $vacataireForbidden,
                            'vacataire_preferred' => $vacatairePreferred,
                            'external_busy' => $externalBusyFlag,
                            'policy_priority' => $unit['policy_priority'] ?? null,
                            'teacher_gap_penalty' => 0,
                        ]);
                    }
                }
            }
        }

        return $candidates;
    }
}
