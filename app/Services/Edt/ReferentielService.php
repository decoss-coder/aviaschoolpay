<?php

namespace App\Services\Edt;

use App\Models\Classe;
use App\Models\EdtPolicy;
use App\Models\EdtReferentielProfil;
use Illuminate\Support\Collection;

class ReferentielService
{
    public function getClasseProfile(Classe $classe): ?EdtReferentielProfil
    {
        return EdtReferentielProfil::query()
            ->where('niveau_code', $classe->niveau_reglementaire_code)
            ->where(function ($q) use ($classe) {
                if ($classe->option_reglementaire_code) {
                    $q->where('option_code', $classe->option_reglementaire_code);
                } else {
                    $q->whereNull('option_code');
                }
            })
            ->where('actif', true)
            ->with('lignes.matiere')
            ->first();
    }

    public function buildDemandUnitsForClasse(Classe $classe, ?EdtPolicy $policy = null): Collection
    {
        $profil = $this->getClasseProfile($classe);
        if (!$profil) {
            return collect();
        }

        return $profil->lignes
            ->sortBy('ordre_montage')
            ->map(function ($ligne) use ($classe) {
                return [
                    'classe_id' => $classe->id,
                    'matiere_id' => $ligne->matiere_id,
                    'matiere_code' => $ligne->matiere?->code,
                    'volume_eleve_minutes' => (int) $ligne->volume_eleve_minutes,
                    'volume_prof_minutes' => (int) $ligne->volume_prof_minutes,
                    'frequence' => $ligne->frequence,
                    'mode_seance' => $ligne->mode_seance,
                    'nb_blocs_souhaite' => (int) $ligne->nb_blocs_souhaite,
                    'blocs_consecutifs' => (bool) $ligne->blocs_consecutifs,
                    'ecart_min_jours' => $ligne->ecart_min_jours,
                    'ordre_montage' => (int) $ligne->ordre_montage,
                    'obligatoire' => (bool) $ligne->obligatoire,
                    'facultatif' => (bool) $ligne->facultatif,
                ];
            })->values();
    }
}