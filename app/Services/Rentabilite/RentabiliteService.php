<?php

namespace App\Services\Rentabilite;

use App\Models\Classe;
use App\Models\Depense;
use App\Models\Enseignant;
use App\Models\Etablissement;
use App\Models\ExerciceComptable;
use App\Models\Inscription;
use App\Models\Paiement;
use Illuminate\Support\Collection;

/**
 * Calcule la rentabilité par établissement / classe / service / centre profit
 * à la volée à partir des paiements confirmés et dépenses approuvées.
 */
class RentabiliteService
{
    public function syntheseGlobale(Etablissement $etab): array
    {
        $exercice = ExerciceComptable::where('etablissement_id', $etab->id)->where('en_cours', true)->first();
        $annee    = $etab->anneesScolaires()->where('en_cours', true)->first();

        $revenus = $annee
            ? Paiement::where('etablissement_id', $etab->id)
                ->where('statut', 'confirme')
                ->pourAnnee($annee->id)
                ->sum('montant')
            : 0;

        $depenses = $exercice
            ? Depense::where('exercice_id', $exercice->id)->approuvees()->sum('montant')
            : 0;

        $nbEleves = $annee
            ? Inscription::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->where('statut', 'validee')
                ->count()
            : 0;

        $masseSalariale = Enseignant::where('etablissement_id', $etab->id)->where('actif', true)->sum('salaire_base');
        $masseAnnuelle  = $masseSalariale * 12;

        $marge        = $revenus - $depenses;
        $tauxMarge    = $revenus > 0 ? round(($marge / $revenus) * 100, 2) : 0;
        $coutMoyenEl  = $nbEleves > 0 ? round($depenses / $nbEleves) : 0;
        $revenuMoyEl  = $nbEleves > 0 ? round($revenus  / $nbEleves) : 0;
        $margeMoyEl   = $revenuMoyEl - $coutMoyenEl;
        $ratioMS      = $revenus > 0 ? round(($masseAnnuelle / $revenus) * 100, 2) : 0;

        return [
            'exercice'       => $exercice?->libelle,
            'annee'          => $annee?->libelle,
            'revenus'        => (int) $revenus,
            'depenses'       => (int) $depenses,
            'marge'          => $marge,
            'taux_marge'     => $tauxMarge,
            'rentable'       => $marge > 0,
            'nb_eleves'      => $nbEleves,
            'revenu_par_eleve' => $revenuMoyEl,
            'cout_par_eleve'   => $coutMoyenEl,
            'marge_par_eleve'  => $margeMoyEl,
            'masse_salariale'    => (int) $masseAnnuelle,
            'masse_salariale_mensuelle' => (int) $masseSalariale,
            'ratio_ms_revenus'   => $ratioMS,
            'ms_saine'           => $ratioMS <= 65,
        ];
    }

    /**
     * Rentabilité par classe (revenus inscriptions allouée + dépenses pondérées)
     */
    public function parClasse(Etablissement $etab): Collection
    {
        $annee = $etab->anneesScolaires()->where('en_cours', true)->first();
        if (! $annee) return collect();

        $exercice = ExerciceComptable::where('etablissement_id', $etab->id)->where('en_cours', true)->first();
        $depenseTotale = $exercice ? (int) Depense::where('exercice_id', $exercice->id)->approuvees()->sum('montant') : 0;
        $totalEleves   = (int) Inscription::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('statut', 'validee')
            ->count();

        $classes = Classe::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->with('niveau:id,libelle')
            ->get();

        return $classes->map(function ($classe) use ($etab, $annee, $depenseTotale, $totalEleves) {
            $nbEleves = (int) Inscription::where('etablissement_id', $etab->id)
                ->where('classe_id', $classe->id)
                ->where('annee_scolaire_id', $annee->id)
                ->where('statut', 'validee')
                ->count();

            $revenus = (int) Paiement::where('etablissement_id', $etab->id)
                ->where('statut', 'confirme')
                ->whereHas('inscription', fn($q) => $q->where('classe_id', $classe->id)->where('annee_scolaire_id', $annee->id))
                ->sum('montant');

            // Allocation au prorata du nombre d'élèves
            $coutAlloue = $totalEleves > 0 ? (int) round(($nbEleves / $totalEleves) * $depenseTotale) : 0;
            $marge      = $revenus - $coutAlloue;
            $tauxMarge  = $revenus > 0 ? round(($marge / $revenus) * 100, 2) : 0;

            return [
                'id'           => $classe->id,
                'nom'          => $classe->nom,
                'niveau'       => $classe->niveau?->libelle,
                'nb_eleves'    => $nbEleves,
                'revenus'      => $revenus,
                'cout_alloue'  => $coutAlloue,
                'marge'        => $marge,
                'taux_marge'   => $tauxMarge,
                'rentable'     => $marge > 0,
                'revenu_par_eleve' => $nbEleves > 0 ? (int) round($revenus / $nbEleves) : 0,
                'cout_par_eleve'   => $nbEleves > 0 ? (int) round($coutAlloue / $nbEleves) : 0,
            ];
        })->sortByDesc('taux_marge')->values();
    }

    /**
     * Rentabilité par service (scolarité/cantine/transport/activités)
     * basée sur le poste_cible des paiements
     */
    public function parService(Etablissement $etab): Collection
    {
        $annee    = $etab->anneesScolaires()->where('en_cours', true)->first();
        $exercice = ExerciceComptable::where('etablissement_id', $etab->id)->where('en_cours', true)->first();
        if (! $annee || ! $exercice) return collect();

        // Revenus regroupés par poste_cible
        $revenusParService = Paiement::where('etablissement_id', $etab->id)
            ->where('statut', 'confirme')
            ->pourAnnee($annee->id)
            ->selectRaw("COALESCE(poste_cible, 'scolarite') as service, SUM(montant) as total")
            ->groupBy('service')
            ->pluck('total', 'service');

        // Dépenses par service via lignes_budgetaires (si budget actif) ou catégories
        $services = ['scolarite', 'cantine', 'transport', 'activites', 'autre'];

        return collect($services)->map(function ($srv) use ($revenusParService) {
            $revenus = (int) ($revenusParService[$srv] ?? 0);
            // Coût estimatif simple : 60% des revenus (à raffiner avec lignes budgétaires futures)
            $cout    = (int) round($revenus * 0.6);
            $marge   = $revenus - $cout;
            return [
                'service'   => ucfirst($srv),
                'revenus'   => $revenus,
                'cout'      => $cout,
                'marge'     => $marge,
                'taux_marge' => $revenus > 0 ? round(($marge / $revenus) * 100, 1) : 0,
                'rentable'  => $marge > 0,
            ];
        })->filter(fn($s) => $s['revenus'] > 0)->values();
    }
}
