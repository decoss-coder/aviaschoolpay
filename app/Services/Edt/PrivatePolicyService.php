<?php

namespace App\Services\Edt;

use App\Models\Classe;
use App\Models\EdtPolicy;
use Illuminate\Support\Collection;

class PrivatePolicyService
{
    public function resolvePolicyForClasse(Classe $classe, ?EdtPolicy $fallback = null): ?EdtPolicy
    {
        if ($classe->edt_policy_id) {
            return EdtPolicy::find($classe->edt_policy_id);
        }

        return $fallback;
    }

    public function applyMatiereOverrides(Collection $units, Classe $classe, ?EdtPolicy $policy): Collection
    {
        if (!$policy) {
            return $units;
        }

        $overrides = $policy->matiereOverrides()
            ->where(function ($q) use ($classe) {
                $q->where('classe_id', $classe->id)
                  ->orWhere(function ($q2) use ($classe) {
                      $q2->whereNull('classe_id')
                         ->where('niveau_reglementaire_code', $classe->niveau_reglementaire_code)
                         ->where(function ($q3) use ($classe) {
                             if ($classe->option_reglementaire_code) {
                                 $q3->where('option_reglementaire_code', $classe->option_reglementaire_code);
                             } else {
                                 $q3->whereNull('option_reglementaire_code');
                             }
                         });
                  });
            })
            ->get()
            ->keyBy('matiere_id');

        return $units->map(function (array $unit) use ($overrides, $policy) {
            $override = $overrides->get($unit['matiere_id']);
            if (!$override) {
                return $unit;
            }

            if (!$override->enabled) {
                $unit['disabled_by_policy'] = true;
                return $unit;
            }

            if ($policy->autoriser_reduction_heures && $override->volume_cible_minutes) {
                $unit['volume_eleve_minutes'] = (int) $override->volume_cible_minutes;
                $unit['volume_prof_minutes'] = min((int) $override->volume_cible_minutes, (int) $unit['volume_prof_minutes']);
            }

            $unit['policy_priority'] = (int) $override->priorite;
            $unit['policy_reason'] = $override->motif;

            return $unit;
        })->reject(fn ($unit) => !empty($unit['disabled_by_policy']))->values();
    }
}