<?php

namespace App\Services\Edt;

use App\Models\Affectation;
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

        if (!$profil || $profil->lignes->isEmpty()) {
            return $this->fromAffectations($classe);
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

    private function fromAffectations(Classe $classe): Collection
    {
        $rows = Affectation::query()
            ->where('classe_id', $classe->id)
            ->where('active', true)
            ->when($classe->annee_scolaire_id, fn ($q) => $q->where('annee_scolaire_id', $classe->annee_scolaire_id))
            ->with('matiere')
            ->orderBy('matiere_id')
            ->get();

        if ($rows->isEmpty() && $classe->annee_scolaire_id) {
            $rows = Affectation::query()
                ->where('classe_id', $classe->id)
                ->where('active', true)
                ->with('matiere')
                ->orderBy('matiere_id')
                ->get();
        }

        $out = collect();
        $ordre = 1;

        foreach ($rows as $row) {
            if (!$row->matiere) {
                continue;
            }

            $volume = (float) ($row->volume_horaire_hebdo ?: 1);
            $nb = max(1, (int) ceil($volume));
            $minutes = (int) round($volume * 60);

            for ($i = 0; $i < $nb; $i++) {
                $out->push([
                    'classe_id' => $classe->id,
                    'matiere_id' => $row->matiere_id,
                    'matiere_code' => $row->matiere?->code,
                    'volume_eleve_minutes' => $minutes,
                    'volume_prof_minutes' => $minutes,
                    'frequence' => 'hebdomadaire',
                    'mode_seance' => 'simple',
                    'nb_blocs_souhaite' => $nb,
                    'blocs_consecutifs' => false,
                    'ecart_min_jours' => null,
                    'ordre_montage' => $ordre++,
                    'obligatoire' => true,
                    'facultatif' => false,
                ]);
            }
        }

        return $out->values();
    }
}
