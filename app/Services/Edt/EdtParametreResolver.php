<?php

namespace App\Services\Edt;

use App\Models\EdtParametre;

class EdtParametreResolver
{
    public function resolve(int $etablissementId, ?int $anneeScolaireId = null): ?EdtParametre
    {
        $specific = EdtParametre::query()
            ->where('etablissement_id', $etablissementId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('actif', true)
            ->first();

        if ($specific) {
            return $specific;
        }

        return EdtParametre::query()
            ->where('etablissement_id', $etablissementId)
            ->whereNull('annee_scolaire_id')
            ->where('actif', true)
            ->first();
    }
}