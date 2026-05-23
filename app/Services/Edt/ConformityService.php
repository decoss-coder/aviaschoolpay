<?php

namespace App\Services\Edt;

use Illuminate\Support\Collection;

class ConformityService
{
    public function build(Collection $classes, Collection $assignments, Collection $issues): array
    {
        $perClasse = $classes->map(function ($classe) use ($assignments) {
            $rows = $assignments->where('classe_id', $classe->id);

            return [
                'classe_id' => $classe->id,
                'classe_nom' => $classe->nom,
                'generated_units' => $rows->count(),
                'matieres' => $rows->pluck('matiere_id')->filter()->unique()->count(),
            ];
        })->values();

        $score = max(0, 100 - ($issues->where('niveau', 'error')->count() * 20) - ($issues->where('niveau', 'warning')->count() * 5));

        return [
            'score_global' => $score,
            'per_classe' => $perClasse,
            'issues_summary' => [
                'errors' => $issues->where('niveau', 'error')->count(),
                'warnings' => $issues->where('niveau', 'warning')->count(),
                'infos' => $issues->where('niveau', 'info')->count(),
            ],
        ];
    }
}