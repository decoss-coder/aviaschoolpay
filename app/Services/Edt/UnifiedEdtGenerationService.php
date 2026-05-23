<?php

namespace App\Services\Edt;

use App\Models\EdtConstraintCatalog;
use App\Models\EdtGenerationRun;
use App\Models\EdtGenerationScenario;
use App\Models\EdtVacataireImport;
use App\Models\EnseignantHoraireExterne;
use App\Models\Enseignant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnifiedEdtGenerationService
{
    public function __construct(
        private readonly EdtParametreResolver $parametreResolver,
        private readonly GenerationPlanner $planner,
        private readonly GenerationApplyService $applyService,
    ) {
    }

    public function generateForCreateScreen(User $user, array $payload): array
    {
        $anneeScolaireId = (int) ($payload['annee_scolaire_id'] ?? 0);

        $parametres = $this->parametreResolver->resolve(
            $user->etablissement_id,
            $anneeScolaireId
        );

        $readiness = $this->checkVacataireReadiness(
            etablissementId: $user->etablissement_id,
            anneeScolaireId: $anneeScolaireId,
            mustWait: (bool) ($parametres?->attendre_horaires_vacataires ?? true),
            blockIfMissing: (bool) ($parametres?->bloquer_si_vacataire_sans_horaire ?? true),
            forceGenerate: (bool) ($payload['force_generate_without_vacataires'] ?? false),
        );

        $scenario = $this->buildOrReuseScenario(
            user: $user,
            payload: $payload,
            parametres: $parametres,
            readiness: $readiness
        );

        $plannerResult = $this->planner->generate($scenario, $user);

        // Le planner peut retourner :
        // - directement un EdtGenerationRun
        // - ou un tableau ['run' => ..., 'assignments' => ...]
        if ($plannerResult instanceof EdtGenerationRun) {
            $run = $plannerResult;
            $plannerAssignments = [];
        } elseif (is_array($plannerResult) && isset($plannerResult['run']) && $plannerResult['run'] instanceof EdtGenerationRun) {
            $run = $plannerResult['run'];
            $plannerAssignments = $this->normalizeAssignmentsArray($plannerResult['assignments'] ?? []);
        } else {
            throw ValidationException::withMessages([
                'ia' => 'Le planner IA a retourné un format inattendu.',
            ]);
        }

        $summary = $this->decodeJsonField($run->summary_json ?? null);
        $conformite = $this->decodeJsonField($run->conformite_json ?? null);

        $assignments = $this->extractAssignmentsPayload(
            summary: $summary,
            conformite: $conformite,
            plannerAssignments: $plannerAssignments
        );

        $summary['assignments_payload'] = $assignments;
        $summary['assignments_count'] = count($assignments);

        if (!empty($readiness['missing_names'])) {
            $summary['vacataires_missing'] = $readiness['missing_names'];
            $summary['vacataires_missing_count'] = count($readiness['missing_names']);
            $summary['vacataires_warning'] = $readiness['message'];
        }

        if (empty($assignments)) {
            $summary['planner_debug'] = [
                'summary_keys' => array_keys($summary),
                'conformite_keys' => array_keys($conformite),
                'planner_assignments_count' => count($plannerAssignments),
            ];
        }

        $run->update([
            'summary_json' => $summary,
            'status' => $run->status ?: 'completed',
        ]);

        $applyImmediately = (bool) ($payload['apply_immediately'] ?? true);

        if (!$applyImmediately) {
            return [
                'run' => $run->fresh(),
                'applied' => false,
                'applied_count' => 0,
                'vacataire_warning' => $readiness['message'] ?? null,
            ];
        }

        if (empty($assignments)) {
            throw ValidationException::withMessages([
                'ia' => 'La génération IA n’a produit aucune proposition exploitable. Vérifie la sortie du planner.',
            ]);
        }

        $count = $this->applyService->apply($run->fresh(), $user);

        return [
            'run' => $run->fresh(),
            'applied' => true,
            'applied_count' => $count,
            'vacataire_warning' => $readiness['message'] ?? null,
        ];
    }

    public function applyRun(EdtGenerationRun $run, User $user): int
    {
        return $this->applyService->apply($run, $user);
    }

    private function buildOrReuseScenario(
        User $user,
        array $payload,
        $parametres,
        array $readiness = []
    ): EdtGenerationScenario {
        return DB::transaction(function () use ($user, $payload, $parametres, $readiness) {
            $scenario = EdtGenerationScenario::create([
                'etablissement_id' => $user->etablissement_id,
                'annee_scolaire_id' => $payload['annee_scolaire_id'],
                'policy_id' => $parametres?->policy_id,
                'nom' => 'Génération IA ' . now()->format('Y-m-d H:i'),
                'mode_generation' => $parametres?->mode_generation_defaut ?? 'prive_equilibre',
                'portee' => $payload['portee'] ?? 'globale',
                'jours_json' => $parametres?->jours_autorises_json ?? ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'],
                'creneaux_json' => $parametres?->creneaux_autorises_json ?? [],
                'salles_json' => $parametres?->salles_autorisees_json ?? [],
                'options_json' => [
                    'from_parametres' => true,
                    'attendre_horaires_vacataires' => (bool) ($parametres?->attendre_horaires_vacataires ?? true),
                    'bloquer_si_vacataire_sans_horaire' => (bool) ($parametres?->bloquer_si_vacataire_sans_horaire ?? true),
                    'respecter_imports_vacataires' => (bool) ($parametres?->respecter_imports_vacataires ?? true),
                    'autoriser_reduction_heures' => (bool) ($parametres?->autoriser_reduction_heures ?? false),
                    'max_reduction_minutes_par_classe' => (int) ($parametres?->max_reduction_minutes_par_classe ?? 0),
                    'max_reduction_minutes_par_matiere' => (int) ($parametres?->max_reduction_minutes_par_matiere ?? 0),
                    'prioriser_classes_examen' => (bool) ($parametres?->prioriser_classes_examen ?? false),
                    'prioriser_permanents' => (bool) ($parametres?->prioriser_permanents ?? true),
                    'respecter_tp_consecutifs' => (bool) ($parametres?->respecter_tp_consecutifs ?? true),
                    'eviter_eps_heures_chaudes' => (bool) ($parametres?->eviter_eps_heures_chaudes ?? true),
                    'limiter_niveaux_prof' => (bool) ($parametres?->limiter_niveaux_prof ?? true),
                    'max_niveaux_par_prof' => (int) ($parametres?->max_niveaux_par_prof ?? 3),
                    'limiter_heures_creuses' => (bool) ($parametres?->limiter_heures_creuses ?? true),
                    'max_heures_creuses_prof' => (int) ($parametres?->max_heures_creuses_prof ?? 2),
                    'autoriser_trous' => (bool) ($parametres?->autoriser_trous ?? false),
                    'tolerer_surcharge_legere' => (bool) ($parametres?->tolerer_surcharge_legere ?? false),
                    'force_generate_without_vacataires' => (bool) ($payload['force_generate_without_vacataires'] ?? false),
                    'vacataire_readiness' => $readiness,
                ],
                'created_by' => $user->id,
            ]);

            if (($payload['portee'] ?? 'globale') === 'classes_selectionnees') {
                foreach (($payload['scope_classes'] ?? []) as $classeId) {
                    DB::table('edt_generation_scenario_scopes')->insert([
                        'scenario_id' => $scenario->id,
                        'scope_type' => 'classe',
                        'scope_id' => (int) $classeId,
                    ]);
                }
            }

            if (class_exists(EdtConstraintCatalog::class)) {
                // Mapping paramètres → contraintes : active/désactive selon les réglages admin
                $options = $scenario->options_json ?? [];
                $constraintOverrides = [
                    'HARD_RESPECT_VACATAIRE_IMPORT'       => (bool) ($options['respecter_imports_vacataires'] ?? true),
                    'SOFT_EPS_HEURES_CHAUDES'             => (bool) ($options['eviter_eps_heures_chaudes'] ?? true),
                    'SOFT_CONSECUTIVE_DISCIPLINE'         => (bool) ($options['respecter_tp_consecutifs'] ?? true),
                    'SOFT_TP_CONSECUTIVE_SAME_DAY'        => (bool) ($options['respecter_tp_consecutifs'] ?? true),
                    'SOFT_EQUITABLE_REPARTITION_SEMAINE'  => (bool) ($options['equilibrer_journees_profs'] ?? true),
                    'SOFT_NO_ISOLATED_HOUR'               => (bool) ($options['limiter_heures_creuses'] ?? false),
                    'SOFT_MAX_3_NIVEAUX_PAR_PROF'         => (bool) ($options['limiter_niveaux_prof'] ?? true),
                ];

                foreach (EdtConstraintCatalog::query()->get() as $constraint) {
                    if ($constraint->is_mandatory) {
                        $enabled = 1;
                    } elseif (array_key_exists($constraint->code, $constraintOverrides)) {
                        $enabled = $constraintOverrides[$constraint->code] ? 1 : 0;
                    } else {
                        $enabled = (int) ($constraint->default_enabled ?? 1);
                    }

                    DB::table('edt_generation_scenario_constraints')->insert([
                        'scenario_id'   => $scenario->id,
                        'constraint_id' => $constraint->id,
                        'enabled'       => $enabled,
                        'weight'        => $constraint->default_weight ?? 1.00,
                        'params_json'   => null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }

            return $scenario;
        });
    }

    private function checkVacataireReadiness(
        int $etablissementId,
        int $anneeScolaireId,
        bool $mustWait,
        bool $blockIfMissing,
        bool $forceGenerate = false
    ): array {
        if (!$mustWait) {
            return [
                'ok' => true,
                'missing_ids' => [],
                'missing_names' => [],
                'message' => null,
            ];
        }

        $vacataires = Enseignant::query()
            ->where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->where('statut', Enseignant::STATUT_VACATAIRE)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'nom', 'prenom']);

        if ($vacataires->isEmpty()) {
            return [
                'ok' => true,
                'missing_ids' => [],
                'missing_names' => [],
                'message' => null,
            ];
        }

        // Vacataires ayant fourni leurs disponibilités directes (import classique)
        $validatedIds = EdtVacataireImport::query()
            ->where('etablissement_id', $etablissementId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('status', 'validated')
            ->distinct()
            ->pluck('enseignant_id')
            ->map(fn ($v) => (int) $v);

        // Vacataires ayant un EDT externe validé (on peut déduire leur disponibilité
        // = tous les créneaux sauf ceux bloqués par l'autre école)
        $externalIds = EnseignantHoraireExterne::query()
            ->whereIn('enseignant_id', $vacataires->pluck('id'))
            ->where('valide', true)
            ->when($anneeScolaireId, fn ($q) =>
                $q->where(fn ($q2) =>
                    $q2->where('annee_scolaire_id', $anneeScolaireId)->orWhereNull('annee_scolaire_id')
                )
            )
            ->distinct()
            ->pluck('enseignant_id')
            ->map(fn ($v) => (int) $v);

        $coveredIds = $validatedIds->merge($externalIds)->unique();

        $missing = $vacataires
            ->filter(fn ($ens) => !$coveredIds->contains((int) $ens->id))
            ->values();

        if ($missing->isEmpty()) {
            return [
                'ok' => true,
                'missing_ids' => [],
                'missing_names' => [],
                'message' => null,
            ];
        }

        $missingNames = $missing
            ->map(fn ($ens) => trim(($ens->prenom ?? '') . ' ' . ($ens->nom ?? '')))
            ->filter()
            ->values()
            ->all();

        $message = 'Vacataires sans horaire validé : ' . implode(', ', $missingNames) . '.';

        if ($blockIfMissing && !$forceGenerate) {
            throw ValidationException::withMessages([
                'ia' => $message . ' Coche “Forcer la génération” si tu veux continuer malgré tout.',
            ]);
        }

        return [
            'ok' => true,
            'missing_ids' => $missing->pluck('id')->map(fn ($v) => (int) $v)->all(),
            'missing_names' => $missingNames,
            'message' => $message,
        ];
    }

    private function decodeJsonField(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function extractAssignmentsPayload(array $summary, array $conformite, array $plannerAssignments = []): array
    {
        $candidates = [
            $summary['assignments_payload'] ?? null,
            $summary['assignments'] ?? null,
            $summary['proposals'] ?? null,
            $summary['payload'] ?? null,
            $summary['result'] ?? null,
            $conformite['assignments_payload'] ?? null,
            $conformite['assignments'] ?? null,
            $plannerAssignments,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeAssignmentsArray($candidate);
            if (!empty($normalized)) {
                return $normalized;
            }
        }

        return [];
    }

    private function normalizeAssignmentsArray(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }

                $jour = strtolower(trim((string) ($item['jour'] ?? '')));
                $creneauId = (int) ($item['creneau_id'] ?? 0);
                $classeId = (int) ($item['classe_id'] ?? 0);
                $matiereId = (int) ($item['matiere_id'] ?? 0);
                $salleId = (int) ($item['salle_id'] ?? 0);
                $enseignantId = !empty($item['enseignant_id']) ? (int) $item['enseignant_id'] : null;

                if (
                    !in_array($jour, ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'], true)
                    || $creneauId <= 0
                    || $classeId <= 0
                    || $matiereId <= 0
                ) {
                    return null;
                }

                return [
                    'jour'          => $jour,
                    'creneau_id'    => $creneauId,
                    'classe_id'     => $classeId,
                    'matiere_id'    => $matiereId,
                    'enseignant_id' => $enseignantId,
                    'salle_id'      => $salleId > 0 ? $salleId : null,
                    'valide_du'     => $item['valide_du'] ?? null,
                    'valide_au'     => $item['valide_au'] ?? null,
                    'ia_score'      => isset($item['ia_score']) ? (float) $item['ia_score'] : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}