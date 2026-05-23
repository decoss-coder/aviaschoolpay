<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AnneeScolaire;
use App\Services\Scolarite\AnneeScolaireContext;
use App\Services\Scolarite\AnneeScolaireService;

trait ResolvesAnneeScolaireCourante
{
    protected function anneeScolaireCourante(?int $etablissementId = null): ?AnneeScolaire
    {
        $ctx = AnneeScolaireContext::courante();
        if ($ctx && ($etablissementId === null || (int) $ctx->etablissement_id === $etablissementId)) {
            return $ctx;
        }

        if ($etablissementId) {
            return AnneeScolaireService::courantePourEtablissement($etablissementId);
        }

        return null;
    }
}
