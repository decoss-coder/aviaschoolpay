<?php

namespace App\Services\Scolarite;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;

class AnneeScolaireDonneesService
{
    /**
     * Aligne eleve.classe_id / statut avec les inscriptions de l'année (après restauration).
     *
     * @return array{eleves: int, classes: int}
     */
    public static function synchroniserDepuisInscriptions(AnneeScolaire $annee): array
    {
        $nbEleves = 0;

        Inscription::query()
            ->where('annee_scolaire_id', $annee->id)
            ->whereNotNull('eleve_id')
            ->whereNotNull('classe_id')
            ->orderBy('id')
            ->each(function (Inscription $inscription) use (&$nbEleves) {
                $eleve = Eleve::query()->find($inscription->eleve_id);
                if (! $eleve || ! $eleve->actif) {
                    return;
                }

                $eleve->update([
                    'classe_id' => $inscription->classe_id,
                    'statut' => 'inscrit',
                ]);
                $nbEleves++;
            });

        $nbClasses = self::recalculerEffectifsClasses($annee);

        return ['eleves' => $nbEleves, 'classes' => $nbClasses];
    }

    public static function recalculerEffectifsClasses(AnneeScolaire $annee): int
    {
        $count = 0;

        Classe::query()
            ->where('annee_scolaire_id', $annee->id)
            ->each(function (Classe $classe) use (&$count) {
                $classe->updateEffectifDepuisInscriptions();
                $count++;
            });

        return $count;
    }
}
