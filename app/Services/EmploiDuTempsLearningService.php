<?php

namespace App\Services;

use App\Models\EmploiDuTempsAdjustment;
use App\Models\EmploiDuTempsLearningRule;

class EmploiDuTempsLearningService
{
    public function rebuildRulesForEtablissement(int $etablissementId, ?int $anneeScolaireId = null): void
    {
        EmploiDuTempsLearningRule::query()
            ->where('etablissement_id', $etablissementId)
            ->when($anneeScolaireId, fn ($q) => $q->where('annee_scolaire_id', $anneeScolaireId))
            ->delete();

        $adjustments = EmploiDuTempsAdjustment::query()
            ->where('etablissement_id', $etablissementId)
            ->when($anneeScolaireId, fn ($q) => $q->where('annee_scolaire_id', $anneeScolaireId))
            ->where('used_for_learning', true)
            ->orderBy('id')
            ->get();

        foreach ($adjustments as $adj) {
            $old = $adj->old_payload ?? [];
            $new = $adj->new_payload ?? [];

            if ($adj->action === 'move') {
                $this->upsertRule(
                    $etablissementId,
                    $anneeScolaireId,
                    $new['classe_id'] ?? null,
                    $new['matiere_id'] ?? null,
                    null,
                    null,
                    $new['creneau_id'] ?? null,
                    $new['jour'] ?? null,
                    'prefer_slot',
                    3
                );

                $this->upsertRule(
                    $etablissementId,
                    $anneeScolaireId,
                    $old['classe_id'] ?? null,
                    $old['matiere_id'] ?? null,
                    null,
                    null,
                    $old['creneau_id'] ?? null,
                    $old['jour'] ?? null,
                    'avoid_slot',
                    2
                );
            }

            if ($adj->action === 'assign_teacher' && !empty($new['enseignant_id'])) {
                $this->upsertRule(
                    $etablissementId,
                    $anneeScolaireId,
                    $new['classe_id'] ?? null,
                    $new['matiere_id'] ?? null,
                    $new['enseignant_id'],
                    null,
                    null,
                    null,
                    'prefer_teacher',
                    3
                );
            }

            if ($adj->action === 'change_room' && !empty($new['salle_id'])) {
                $this->upsertRule(
                    $etablissementId,
                    $anneeScolaireId,
                    $new['classe_id'] ?? null,
                    $new['matiere_id'] ?? null,
                    null,
                    $new['salle_id'],
                    null,
                    null,
                    'prefer_room',
                    2
                );
            }

            if ($adj->action === 'lock' && !empty($new['jour']) && !empty($new['creneau_id'])) {
                $this->upsertRule(
                    $etablissementId,
                    $anneeScolaireId,
                    $new['classe_id'] ?? null,
                    $new['matiere_id'] ?? null,
                    $new['enseignant_id'] ?? null,
                    $new['salle_id'] ?? null,
                    $new['creneau_id'] ?? null,
                    $new['jour'] ?? null,
                    'fixed_slot',
                    5
                );
            }
        }
    }

    private function upsertRule(
        int $etablissementId,
        ?int $anneeScolaireId,
        ?int $classeId,
        ?int $matiereId,
        ?int $enseignantId,
        ?int $salleId,
        ?int $creneauId,
        ?string $jour,
        string $ruleType,
        float $weight
    ): void {
        $rule = EmploiDuTempsLearningRule::firstOrNew([
            'etablissement_id' => $etablissementId,
            'annee_scolaire_id' => $anneeScolaireId,
            'classe_id' => $classeId,
            'matiere_id' => $matiereId,
            'enseignant_id' => $enseignantId,
            'salle_id' => $salleId,
            'creneau_id' => $creneauId,
            'jour' => $jour,
            'rule_type' => $ruleType,
        ]);

        $rule->weight = ($rule->exists ? (float) $rule->weight : 0) + $weight;
        $rule->hits = ($rule->exists ? (int) $rule->hits : 0) + 1;
        $rule->active = true;
        $rule->save();
    }
}