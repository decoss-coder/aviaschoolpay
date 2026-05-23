<?php

namespace App\Services\Edt;

use App\Models\EdtGenerationRun;
use App\Models\EmploiDuTemps;
use App\Models\EmploiDuTempsAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GenerationApplyService
{
    public function apply(EdtGenerationRun $run, User $user): int
    {
        $summary = is_array($run->summary_json ?? null)
            ? ($run->summary_json ?? [])
            : (json_decode($run->summary_json ?? '[]', true) ?: []);

        $assignments = $this->extractAssignments($summary);

        if (empty($assignments)) {
            throw ValidationException::withMessages([
                'ia' => 'Aucune affectation exploitable n’a été trouvée dans ce run.',
            ]);
        }

        return DB::transaction(function () use ($run, $user, $assignments, $summary) {
            $count = 0;

            $this->clearExistingIaRowsInScope($run);

            foreach ($assignments as $item) {
                $data = $this->normalizeAssignment($item, $run, $user);

                if (!$data) {
                    continue;
                }

                $existing = EmploiDuTemps::query()
                    ->where('etablissement_id', $run->etablissement_id)
                    ->where('annee_scolaire_id', $run->annee_scolaire_id)
                    ->where('jour', $data['jour'])
                    ->where('creneau_id', $data['creneau_id'])
                    ->where('classe_id', $data['classe_id'])
                    ->first();

                if ($existing && (bool) ($existing->locked_by_user ?? false)) {
                    continue;
                }

                if ($existing) {
                    $before = $existing->only([
                        'annee_scolaire_id',
                        'jour',
                        'creneau_id',
                        'classe_id',
                        'matiere_id',
                        'enseignant_id',
                        'salle_id',
                        'actif',
                    ]);

                    $existing->fill($data);
                    $existing->save();

                    $this->logAdjustment(
                        emploiId: $existing->id,
                        etablissementId: $run->etablissement_id,
                        anneeScolaireId: $run->annee_scolaire_id,
                        userId: $user->id,
                        action: 'update',
                        generationUuid: $run->run_uuid,
                        oldPayload: $before,
                        newPayload: $existing->fresh()->only([
                            'annee_scolaire_id',
                            'jour',
                            'creneau_id',
                            'classe_id',
                            'matiere_id',
                            'enseignant_id',
                            'salle_id',
                            'actif',
                        ]),
                        reason: 'Application automatique du run IA'
                    );
                } else {
                    $emploi = EmploiDuTemps::create($data);

                    $this->logAdjustment(
                        emploiId: $emploi->id,
                        etablissementId: $run->etablissement_id,
                        anneeScolaireId: $run->annee_scolaire_id,
                        userId: $user->id,
                        action: 'create',
                        generationUuid: $run->run_uuid,
                        oldPayload: null,
                        newPayload: $emploi->only([
                            'annee_scolaire_id',
                            'jour',
                            'creneau_id',
                            'classe_id',
                            'matiere_id',
                            'enseignant_id',
                            'salle_id',
                            'actif',
                        ]),
                        reason: 'Application automatique du run IA'
                    );
                }

                $count++;
            }

            $summary['applied_count'] = $count;

            $run->update([
                'status' => 'applied',
                'summary_json' => $summary,
                'finished_at' => $run->finished_at ?: now(),
            ]);

            return $count;
        });
    }

    private function extractAssignments(array $summary): array
    {
        if (!empty($summary['assignments_payload']) && is_array($summary['assignments_payload'])) {
            return array_values($summary['assignments_payload']);
        }

        if (!empty($summary['assignments']) && is_array($summary['assignments'])) {
            return array_values($summary['assignments']);
        }

        if (!empty($summary['proposals']) && is_array($summary['proposals'])) {
            return array_values($summary['proposals']);
        }

        return [];
    }

    private function normalizeAssignment(array $item, EdtGenerationRun $run, User $user): ?array
    {
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
            'etablissement_id' => $run->etablissement_id,
            'annee_scolaire_id' => $run->annee_scolaire_id,
            'jour' => $jour,
            'creneau_id' => $creneauId,
            'classe_id' => $classeId,
            'matiere_id' => $matiereId,
            'enseignant_id' => $enseignantId,
            'salle_id' => $salleId > 0 ? $salleId : null,
            'valide_du' => $item['valide_du'] ?? null,
            'valide_au' => $item['valide_au'] ?? null,
            'actif' => true,
            'source' => 'ia',
            'generation_uuid' => $run->run_uuid,
            'locked_by_user' => false,
            'ia_score' => isset($item['ia_score']) ? (float) $item['ia_score'] : ($run->score_global ?? null),
            'last_adjusted_by' => $user->id,
            'last_adjusted_at' => now(),
        ];
    }

    private function clearExistingIaRowsInScope(EdtGenerationRun $run): void
    {
        $query = EmploiDuTemps::query()
            ->where('etablissement_id', $run->etablissement_id)
            ->where('annee_scolaire_id', $run->annee_scolaire_id)
            ->where('source', 'ia')
            ->where(function ($q) {
                $q->whereNull('locked_by_user')->orWhere('locked_by_user', false);
            });

        $scenario = method_exists($run, 'scenario') ? $run->scenario()->first() : null;

        if ($scenario && $scenario->portee === 'classes_selectionnees' && Schema::hasTable('edt_generation_scenario_scopes')) {
            $classIds = DB::table('edt_generation_scenario_scopes')
                ->where('scenario_id', $scenario->id)
                ->where('scope_type', 'classe')
                ->pluck('scope_id')
                ->map(fn ($v) => (int) $v)
                ->filter()
                ->values();

            if ($classIds->isNotEmpty()) {
                $query->whereIn('classe_id', $classIds->all());
            }
        }

        $query->delete();
    }

    private function logAdjustment(
        int $emploiId,
        int $etablissementId,
        ?int $anneeScolaireId,
        ?int $userId,
        string $action,
        ?string $generationUuid,
        $oldPayload,
        $newPayload,
        ?string $reason = null
    ): void {
        if (!Schema::hasTable('emploi_du_temps_adjustments')) {
            return;
        }

        EmploiDuTempsAdjustment::create([
            'emploi_du_temps_id' => $emploiId,
            'etablissement_id' => $etablissementId,
            'annee_scolaire_id' => $anneeScolaireId,
            'user_id' => $userId,
            'action' => $action,
            'generation_uuid' => $generationUuid,
            'old_payload' => $oldPayload,
            'new_payload' => $newPayload,
            'reason' => $reason,
            'used_for_learning' => 1,
        ]);
    }
}