<?php

namespace App\Services\EmploiDuTemps;

use App\Models\EmploiDuTemps;
use App\Models\Salle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EmploiDuTempsConflictGuard
{
    public function createSafely(array $data): EmploiDuTemps
    {
        return DB::transaction(function () use ($data) {
            $this->assertNoConflicts($data, null, true);

            return EmploiDuTemps::create($data);
        });
    }

    public function assertNoConflicts(array $data, ?int $ignoreId = null, bool $lock = false): void
    {
        $base = EmploiDuTemps::query()
            ->where('etablissement_id', $data['etablissement_id'])
            ->where('annee_scolaire_id', $data['annee_scolaire_id'])
            ->where('jour', $data['jour'])
            ->where('creneau_id', $data['creneau_id'])
            ->where('actif', true);

        if ($ignoreId) {
            $base->whereKeyNot($ignoreId);
        }

        if ($lock) {
            $base->lockForUpdate();
        }

        if ((clone $base)->where('classe_id', $data['classe_id'])->exists()) {
            throw ValidationException::withMessages(['classe_id' => 'Cette classe a déjà un cours sur ce créneau.']);
        }

        if (!empty($data['enseignant_id']) && (clone $base)->where('enseignant_id', $data['enseignant_id'])->exists()) {
            throw ValidationException::withMessages(['enseignant_id' => 'Cet enseignant est déjà occupé sur ce créneau.']);
        }

        if (!empty($data['salle_id'])) {
            $salle = Salle::where('id', $data['salle_id'])
                ->where('etablissement_id', $data['etablissement_id'])
                ->first();

            $colonneExiste = Schema::hasColumn('salles', 'capacite_groupes');
            $estExclusive = !$colonneExiste || ($salle && $salle->capacite_groupes <= 1);

            if ($estExclusive && (clone $base)->where('salle_id', $data['salle_id'])->exists()) {
                throw ValidationException::withMessages(['salle_id' => 'Cette salle est déjà occupée sur ce créneau.']);
            }
        }
    }
}
