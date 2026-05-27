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
            ->filter(fn ($ligne) => $this->matiereAutoriseePourClasse($classe, $ligne->matiere?->code, $ligne->matiere?->nom))
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

            if (! $this->matiereAutoriseePourClasse($classe, $row->matiere?->code, $row->matiere?->nom)) {
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

    private function matiereAutoriseePourClasse(Classe $classe, ?string $code, ?string $nom): bool
    {
        if ($this->estLv2($code, $nom)) {
            return $this->classeAccepteLv2($classe);
        }

        if ($this->estPhilosophie($code, $nom)) {
            return $this->classeEstSecondCycle($classe);
        }

        return true;
    }

    private function estLv2(?string $code, ?string $nom): bool
    {
        $text = $this->normaliserTexte(trim((string) $code.' '.(string) $nom));

        return str_contains($text, 'lv2')
            || str_contains($text, 'espagnol')
            || str_contains($text, 'esp')
            || str_contains($text, 'allemand')
            || str_contains($text, 'all');
    }

    private function estPhilosophie(?string $code, ?string $nom): bool
    {
        $text = $this->normaliserTexte(trim((string) $code.' '.(string) $nom));

        return str_contains($text, 'philo');
    }

    private function classeAccepteLv2(Classe $classe): bool
    {
        if ($this->classeEstSecondCycle($classe)) {
            return true;
        }

        return preg_match('/(^|\s)(4|3)\s*(e|eme)?(\s|$)/', $this->classeTexte($classe)) === 1;
    }

    private function classeEstSecondCycle(Classe $classe): bool
    {
        $txt = $this->classeTexte($classe);

        return str_contains($txt, 'second_cycle')
            || str_contains($txt, 'second cycle')
            || str_contains($txt, 'seconde')
            || str_contains($txt, '2nde')
            || str_contains($txt, '1ere')
            || str_contains($txt, 'premiere')
            || str_contains($txt, 'tle')
            || str_contains($txt, 'terminale');
    }

    private function classeTexte(Classe $classe): string
    {
        $classe->loadMissing('niveau');
        $niveau = $classe->niveau;

        return $this->normaliserTexte(trim(
            (string) ($classe->nom ?? '').' '.
            (string) ($niveau?->code ?? '').' '.
            (string) ($niveau?->libelle ?? '').' '.
            (string) ($niveau?->cycle ?? '')
        ));
    }

    private function normaliserTexte(string $value): string
    {
        $value = strtolower(trim($value));
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        ]);

        return preg_replace('/\s+/', ' ', $value) ?: '';
    }
}
