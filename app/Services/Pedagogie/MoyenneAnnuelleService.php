<?php

namespace App\Services\Pedagogie;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\MoyenneAnnuelle;
use App\Models\MoyenneGenerale;
use App\Models\Trimestre;
use Illuminate\Support\Facades\DB;

/**
 * Calcule la moyenne annuelle d'un élève à partir de ses moyennes
 * trimestrielles, pondérées par le coefficient de chaque trimestre.
 *
 * Système ivoirien standard :  T1×1, T2×2, T3×2  → total coefs = 5
 * Moyenne annuelle = Σ(moy_trim × coef_trim) / Σ(coef_trim)
 */
class MoyenneAnnuelleService
{
    /**
     * Calcule (ou recalcule) la moyenne annuelle d'un élève pour une année.
     */
    public static function calculerPourEleve(Eleve $eleve, AnneeScolaire $annee, ?Classe $classe = null): ?MoyenneAnnuelle
    {
        $trimestres = Trimestre::where('annee_scolaire_id', $annee->id)->get();
        if ($trimestres->isEmpty()) {
            return null;
        }

        $moyennes = MoyenneGenerale::where('eleve_id', $eleve->id)
            ->whereIn('trimestre_id', $trimestres->pluck('id'))
            ->whereNotNull('moyenne_generale')
            ->get()
            ->keyBy('trimestre_id');

        if ($moyennes->isEmpty()) {
            return null;
        }

        $totalPoints = 0.0;
        $totalCoefs  = 0.0;

        foreach ($trimestres as $t) {
            $moy = $moyennes->get($t->id);
            if (! $moy) {
                continue;
            }
            $coef = (float) ($t->coefficient ?? 1);
            $totalPoints += (float) $moy->moyenne_generale * $coef;
            $totalCoefs  += $coef;
        }

        if ($totalCoefs <= 0) {
            return null;
        }

        $moyAnnuelle = round($totalPoints / $totalCoefs, 2);
        $classeId    = $classe?->id ?? $moyennes->first()->classe_id;
        $mention     = self::calculerMention($moyAnnuelle);
        $decision    = self::suggererDecision($moyAnnuelle);

        return MoyenneAnnuelle::updateOrCreate(
            [
                'eleve_id'          => $eleve->id,
                'annee_scolaire_id' => $annee->id,
            ],
            [
                'classe_id'        => $classeId,
                'moyenne_annuelle' => $moyAnnuelle,
                'mention'          => $mention,
                'decision_finale'  => $decision,
            ]
        );
    }

    /**
     * Recalcule pour toute une classe + ses rangs annuels.
     */
    public static function calculerPourClasse(Classe $classe, AnneeScolaire $annee): int
    {
        $eleves = Eleve::where('classe_id', $classe->id)->where('actif', true)->get();
        $count = 0;

        DB::transaction(function () use ($eleves, $annee, $classe, &$count) {
            foreach ($eleves as $eleve) {
                $m = self::calculerPourEleve($eleve, $annee, $classe);
                if ($m) {
                    $count++;
                }
            }
            self::calculerRangsAnnuels($classe, $annee);
        });

        return $count;
    }

    public static function calculerRangsAnnuels(Classe $classe, AnneeScolaire $annee): void
    {
        $moyennes = MoyenneAnnuelle::where('classe_id', $classe->id)
            ->where('annee_scolaire_id', $annee->id)
            ->whereNotNull('moyenne_annuelle')
            ->orderByDesc('moyenne_annuelle')
            ->get();

        foreach ($moyennes as $i => $m) {
            $m->update(['rang_annuel' => $i + 1]);
        }
    }

    public static function calculerMention(?float $moy): string
    {
        if ($moy === null) return 'aucune';
        if ($moy >= 16)    return 'felicitations';
        if ($moy >= 14)    return 'tableau_honneur';
        if ($moy >= 12)    return 'encouragements';
        if ($moy < 8)      return 'avertissement';
        return 'aucune';
    }

    public static function suggererDecision(?float $moy): string
    {
        if ($moy === null) return 'en_attente';
        if ($moy >= 10)    return 'admis';
        if ($moy >= 8)     return 'redoublement_propose';
        return 'redoublement';
    }
}
