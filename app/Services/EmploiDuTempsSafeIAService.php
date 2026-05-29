<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\EmploiDuTemps;
use App\Models\Salle;
use App\Services\EmploiDuTemps\EmploiDuTempsConflictGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmploiDuTempsSafeIAService extends EmploiDuTempsIAService
{
    public function genererPourClasse(Classe $classe, AnneeScolaire $annee, array $params = []): array
    {
        $result = $this->suggererPourClasse($classe, $annee, $params);

        if (!($result['success'] ?? false)) {
            return $result;
        }

        $created = 0;
        $ignored = 0;
        $generationUuid = (string) Str::uuid();
        $guard = app(EmploiDuTempsConflictGuard::class);

        DB::transaction(function () use ($result, &$created, &$ignored, $generationUuid, $classe, $annee, $guard) {
            foreach ($result['propositions'] as $item) {
                if (EmploiDuTemps::query()
                    ->where('etablissement_id', $classe->etablissement_id)
                    ->where('annee_scolaire_id', $annee->id)
                    ->where('jour', $item['jour'])
                    ->where('creneau_id', $item['creneau_id'])
                    ->where('locked_by_user', true)
                    ->where('actif', true)
                    ->exists()) {
                    $ignored++;
                    continue;
                }

                unset($item['_meta']);
                $item['generation_uuid'] = $generationUuid;
                $item['etablissement_id'] = $classe->etablissement_id;
                $item['annee_scolaire_id'] = $annee->id;
                $item['actif'] = $item['actif'] ?? true;

                try {
                    $guard->createSafely($item);
                    $created++;
                } catch (ValidationException $e) {
                    $ignored++;
                }
            }
        });

        return [
            'success' => true,
            'message' => null,
            'generation_uuid' => $generationUuid,
            'created' => $created,
            'ignored' => $ignored,
            'alertes' => $result['alertes'],
            'non_places' => $result['non_places'],
            'score' => $result['score'],
        ];
    }

    public function detecterConflits(int $etablissementId, ?int $anneeScolaireId = null): array
    {
        $query = EmploiDuTemps::query()
            ->where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->with(['classe', 'enseignant', 'salle', 'creneau', 'matiere']);

        if ($anneeScolaireId) {
            $query->where('annee_scolaire_id', $anneeScolaireId);
        }

        $items = $query->get();
        $conflicts = [];

        $this->pushSafeGroupedConflicts($conflicts, $items, fn ($i) => $i->classe_id . '|' . $i->jour . '|' . $i->creneau_id, 'classe');
        $this->pushSafeGroupedConflicts($conflicts, $items->filter(fn ($i) => !empty($i->enseignant_id)), fn ($i) => $i->enseignant_id . '|' . $i->jour . '|' . $i->creneau_id, 'enseignant');

        $colonneCapaciteExiste = Schema::hasColumn('salles', 'capacite_groupes');
        $roomItems = $items->filter(function ($i) use ($colonneCapaciteExiste) {
            if (empty($i->salle_id)) {
                return false;
            }

            if (! $colonneCapaciteExiste) {
                return true;
            }

            return optional($i->salle)->capacite_groupes <= 1;
        });

        $this->pushSafeGroupedConflicts($conflicts, $roomItems, fn ($i) => $i->salle_id . '|' . $i->jour . '|' . $i->creneau_id, 'salle');

        return $conflicts;
    }

    private function pushSafeGroupedConflicts(array &$conflicts, $items, callable $keyResolver, string $type): void
    {
        $items->groupBy($keyResolver)->each(function ($groupItems, $groupKey) use (&$conflicts, $type) {
            if ($groupItems->count() <= 1) {
                return;
            }

            $first = $groupItems->first();
            $conflicts[] = [
                'type' => $type,
                'key' => $groupKey,
                'label' => $this->buildConflictLabel($type, $first),
                'items' => $groupItems->values(),
            ];
        });
    }

    private function buildConflictLabel(string $type, EmploiDuTemps $item): string
    {
        $jour = ucfirst((string) $item->jour);
        $creneau = optional($item->creneau)->libelle ?? '—';

        return match ($type) {
            'classe' => 'Classe : ' . (optional($item->classe)->nom ?? '—') . ' / ' . $jour . ' / ' . $creneau,
            'enseignant' => 'Enseignant : ' . (optional($item->enseignant)->nom_complet ?? '—') . ' / ' . $jour . ' / ' . $creneau,
            'salle' => 'Salle : ' . (optional($item->salle)->nom ?? '—') . ' / ' . $jour . ' / ' . $creneau,
            default => $jour . ' / ' . $creneau,
        };
    }
}
