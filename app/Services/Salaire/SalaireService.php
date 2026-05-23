<?php

namespace App\Services\Salaire;

use App\Models\Enseignant;
use App\Models\Pointage;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcule les heures travaillées et le salaire mensuel d'un enseignant
 * à partir de ses pointages réels (paires arrivee+depart).
 *
 * Règles :
 *  - type_remuneration = 'fixe'     → salaire = salaire_base (heures purement indicatives)
 *  - type_remuneration = 'horaire'  → salaire = heures × taux_horaire
 *  - type_remuneration = 'mixte'    → salaire = salaire_base + (heures × taux_horaire)
 *
 *  - Cotisations sociales (CNPS part salariale) : 5,5 % du brut
 *  - IUTS (impôt) : 1,5 % du brut (simplifié — à ajuster selon barème pays)
 */
class SalaireService
{
    public const TAUX_COTISATIONS_SOCIALES = 5.5;
    public const TAUX_IUTS = 1.5;

    public function calculerPourEnseignant(Enseignant $enseignant, string $mois): array
    {
        [$debut, $fin] = $this->bornesMois($mois);

        $detail = $this->calculerHeuresTravaillees($enseignant->id, $debut, $fin);

        $heures = $detail['heures_travaillees'];
        $type   = $enseignant->type_remuneration ?? 'fixe';
        $base   = (int) ($enseignant->salaire_base ?? 0);
        $taux   = (int) ($enseignant->taux_horaire ?? 0);

        $montantHoraire = match ($type) {
            'horaire' => (int) round($heures * $taux),
            'mixte'   => (int) round($heures * $taux),
            default   => 0,
        };

        $salaireBaseRetenu = match ($type) {
            'horaire' => 0,
            default   => $base,
        };

        $brut = $salaireBaseRetenu + $montantHoraire;
        $cnps  = (int) round($brut * self::TAUX_COTISATIONS_SOCIALES / 100);
        $iuts  = (int) round($brut * self::TAUX_IUTS / 100);
        $net   = $brut - $cnps - $iuts;

        return [
            'mois'                  => $mois,
            'periode_debut'         => $debut->toDateString(),
            'periode_fin'           => $fin->toDateString(),
            'type_remuneration'     => $type,
            'salaire_base'          => $salaireBaseRetenu,
            'taux_horaire'          => $taux,
            'heures_travaillees'    => round($heures, 2),
            'heures_contractuelles' => $enseignant->heures_contractuelles_mois,
            'montant_horaire'       => $montantHoraire,
            'salaire_brut'          => $brut,
            'cotisations_sociales'  => $cnps,
            'impots'                => $iuts,
            'salaire_net'           => $net,
            'nb_jours_travailles'   => $detail['nb_jours_travailles'],
            'nb_jours_absents'      => $detail['nb_jours_absents'],
            'nb_retards'            => $detail['nb_retards'],
            'detail_journalier'     => $detail['journalier'],
        ];
    }

    /**
     * Calcule les heures travaillées en pariant les pointages arrivée + départ.
     * Si un jour n'a qu'une arrivée sans départ : 0 h (ignoré).
     */
    private function calculerHeuresTravaillees(int $enseignantId, Carbon $debut, Carbon $fin): array
    {
        $pointages = Pointage::where('enseignant_id', $enseignantId)
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->whereIn('statut', ['present', 'retard'])
            ->orderBy('date')
            ->orderBy('heure_scan')
            ->get(['date', 'type_scan', 'heure_scan', 'statut']);

        $journalier = collect();
        $totalHeures = 0.0;
        $nbJoursTravailles = 0;
        $nbRetards = 0;

        $parJour = $pointages->groupBy(fn($p) => $p->date->toDateString());

        foreach ($parJour as $date => $scans) {
            $arrivee = $scans->where('type_scan', 'arrivee')->first();
            $depart  = $scans->where('type_scan', 'depart')->last();

            $heures = 0.0;
            $estComplet = false;

            if ($arrivee && $depart) {
                $hArr = Carbon::parse($arrivee->date->toDateString().' '.$arrivee->heure_scan);
                $hDep = Carbon::parse($depart->date->toDateString().' '.$depart->heure_scan);
                $diff = $hDep->diffInMinutes($hArr);
                $heures = round($diff / 60, 2);
                $estComplet = true;
                $nbJoursTravailles++;
                $totalHeures += $heures;
            }

            if ($arrivee && $arrivee->statut === 'retard') {
                $nbRetards++;
            }

            $journalier->push([
                'date'    => $date,
                'arrivee' => $arrivee?->heure_scan,
                'depart'  => $depart?->heure_scan,
                'heures'  => $heures,
                'retard'  => $arrivee?->statut === 'retard',
                'complet' => $estComplet,
            ]);
        }

        // Jours ouvrés du mois (lundi-vendredi)
        $joursOuvres = 0;
        $current = $debut->copy();
        while ($current <= $fin) {
            if ($current->isWeekday()) $joursOuvres++;
            $current->addDay();
        }
        $nbJoursAbsents = max(0, $joursOuvres - $nbJoursTravailles);

        return [
            'heures_travaillees'   => $totalHeures,
            'nb_jours_travailles'  => $nbJoursTravailles,
            'nb_jours_absents'     => $nbJoursAbsents,
            'nb_retards'           => $nbRetards,
            'journalier'           => $journalier,
        ];
    }

    private function bornesMois(string $mois): array
    {
        $debut = Carbon::parse($mois.'-01')->startOfMonth();
        $fin   = (clone $debut)->endOfMonth();
        return [$debut, $fin];
    }

    /**
     * Aperçu rapide (sans persister) — pour la pré-visualisation côté UI.
     */
    public function previsualiser(Enseignant $enseignant, string $mois): array
    {
        return $this->calculerPourEnseignant($enseignant, $mois);
    }
}
