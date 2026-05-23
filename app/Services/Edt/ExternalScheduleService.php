<?php

namespace App\Services\Edt;

use App\Models\EnseignantHoraireExterne;
use Illuminate\Support\Collection;

/**
 * Construit la carte des créneaux "déjà occupés" pour les enseignants
 * qui travaillent dans d'autres établissements.
 *
 * La carte retournée est indexée : [enseignant_id][jour][creneau_id] = true
 * Elle est utilisée par GenerationPlanner pour marquer external_busy sur chaque candidat,
 * et par ConstraintEngine pour bloquer le placement via HARD_NO_TEACHER_EXTERNAL_COLLISION.
 */
class ExternalScheduleService
{
    /**
     * @param Collection $enseignants  Collection de App\Models\Enseignant
     * @param Collection $creneaux     Collection de App\Models\Creneau (type cours)
     * @param int|null   $anneeScolaireId
     * @return array [enseignant_id => [jour => [creneau_id => true]]]
     */
    public function getBusyMap(Collection $enseignants, Collection $creneaux, ?int $anneeScolaireId = null): array
    {
        $enseignantIds = $enseignants->pluck('id');

        $query = EnseignantHoraireExterne::whereIn('enseignant_id', $enseignantIds)
            ->where('valide', true);

        if ($anneeScolaireId) {
            $query->where(function ($q) use ($anneeScolaireId) {
                $q->where('annee_scolaire_id', $anneeScolaireId)
                  ->orWhereNull('annee_scolaire_id');
            });
        }

        $slots = $query->get();

        $map = [];

        // Les créneaux n'ont pas de champ "jour" (ils s'appliquent à tous les jours).
        // On compare uniquement les plages horaires : si le slot externe (jour+heures)
        // chevauche un créneau local, ce créneau est bloqué pour ce jour.
        foreach ($slots as $slot) {
            foreach ($creneaux as $creneau) {
                if ($this->overlaps($slot->heure_debut, $slot->heure_fin, $creneau->heure_debut, $creneau->heure_fin)) {
                    $map[$slot->enseignant_id][$slot->jour][$creneau->id] = true;
                }
            }
        }

        return $map;
    }

    /**
     * Résumé lisible des conflits externes d'un enseignant pour affichage UI.
     *
     * @return array [['jour' => ..., 'heure_debut' => ..., 'heure_fin' => ..., 'ecole' => ...], ...]
     */
    public function getSlotsForEnseignant(int $enseignantId, ?int $anneeScolaireId = null): Collection
    {
        $query = EnseignantHoraireExterne::where('enseignant_id', $enseignantId)
            ->where('valide', true)
            ->orderBy('jour')
            ->orderBy('heure_debut');

        if ($anneeScolaireId) {
            $query->where(function ($q) use ($anneeScolaireId) {
                $q->where('annee_scolaire_id', $anneeScolaireId)
                  ->orWhereNull('annee_scolaire_id');
            });
        }

        return $query->get();
    }

    private function overlaps(string $debutA, string $finA, string $debutB, string $finB): bool
    {
        // A chevauche B si début_A < fin_B ET fin_A > début_B
        return $debutA < $finB && $finA > $debutB;
    }
}
