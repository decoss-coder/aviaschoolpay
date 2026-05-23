<?php

namespace App\Services\Rapports;

use App\Models\Classe;
use App\Models\Depense;
use App\Models\Etablissement;
use App\Models\Inscription;
use App\Models\Paiement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Produit les données chiffrées pour les rapports financiers PDF.
 * Tous les calculs proviennent de paiements confirmés et dépenses approuvées.
 */
class RapportFinancierService
{
    /**
     * État détaillé des paiements pour une période donnée.
     */
    public function paiements(Etablissement $etab, ?string $debut = null, ?string $fin = null, ?int $classeId = null): array
    {
        $debut = $debut ?: now()->startOfMonth()->toDateString();
        $fin   = $fin   ?: now()->endOfMonth()->toDateString();

        $query = Paiement::where('etablissement_id', $etab->id)
            ->where('statut', 'confirme')
            ->whereBetween('date_paiement', [$debut, $fin])
            ->with(['eleve:id,nom,prenom,matricule_interne', 'inscription.classe:id,nom']);

        if ($classeId) {
            $query->whereHas('inscription', fn($q) => $q->where('classe_id', $classeId));
        }

        $paiements = $query->orderBy('date_paiement')->orderBy('id')->get();

        $total       = (int) $paiements->sum('montant');
        $totalInsc   = (int) $paiements->sum('montant_inscription');
        $totalScol   = (int) $paiements->sum('montant_scolarite');

        $parMode = $paiements->groupBy('mode')->map(fn($g) => [
            'mode'    => $g->first()->mode,
            'nombre'  => $g->count(),
            'montant' => (int) $g->sum('montant'),
        ])->values();

        $parClasse = $paiements
            ->filter(fn($p) => $p->inscription?->classe)
            ->groupBy(fn($p) => $p->inscription->classe->id)
            ->map(fn($g) => [
                'classe'  => $g->first()->inscription->classe->nom,
                'nombre'  => $g->count(),
                'montant' => (int) $g->sum('montant'),
            ])->values();

        return compact('debut', 'fin', 'paiements', 'total', 'totalInsc', 'totalScol', 'parMode', 'parClasse');
    }

    /**
     * Bilan scolarité pour l'année scolaire en cours (ou spécifiée).
     * Montre : revenus, restes à payer, par classe, top débiteurs.
     */
    public function bilanScolarite(Etablissement $etab, ?int $anneeId = null): array
    {
        $annee = $anneeId
            ? $etab->anneesScolaires()->where('id', $anneeId)->first()
            : $etab->anneesScolaires()->where('en_cours', true)->first();

        if (! $annee) {
            return ['annee' => null, 'classes' => collect(), 'totaux' => null, 'topDebiteurs' => collect()];
        }

        $inscriptions = Inscription::where('etablissement_id', $etab->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('statut', 'validee')
            ->with(['classe:id,nom,niveau_id', 'classe.niveau:id,libelle', 'eleve:id,nom,prenom,matricule_interne', 'paiements'])
            ->get();

        // Calcul des soldes par inscription
        $inscriptionsCalc = $inscriptions->map(function ($i) {
            $paye = (int) $i->paiements->where('statut', 'confirme')->sum('montant');
            $i->montant_paye_calc = $paye;
            $i->reste_calc = max(0, (int) $i->montant_net - $paye);
            $i->taux_calc = $i->montant_net > 0 ? round(($paye / $i->montant_net) * 100, 1) : 0;
            return $i;
        });

        // Synthèse par classe
        $parClasse = $inscriptionsCalc->groupBy('classe_id')->map(function ($insc) {
            $first = $insc->first();
            return [
                'classe'      => $first->classe?->nom ?? '—',
                'niveau'      => $first->classe?->niveau?->libelle ?? '—',
                'nb_eleves'   => $insc->count(),
                'du_total'    => (int) $insc->sum('montant_net'),
                'paye'        => (int) $insc->sum('montant_paye_calc'),
                'reste'       => (int) $insc->sum('reste_calc'),
                'taux'        => $insc->sum('montant_net') > 0
                    ? round(($insc->sum('montant_paye_calc') / $insc->sum('montant_net')) * 100, 1)
                    : 0,
                'a_jour'      => $insc->where('reste_calc', 0)->count(),
                'en_retard'   => $insc->where('reste_calc', '>', 0)->count(),
            ];
        })->sortBy('classe')->values();

        $totaux = [
            'nb_eleves' => $inscriptionsCalc->count(),
            'du_total'  => (int) $inscriptionsCalc->sum('montant_net'),
            'paye'      => (int) $inscriptionsCalc->sum('montant_paye_calc'),
            'reste'     => (int) $inscriptionsCalc->sum('reste_calc'),
            'taux'      => $inscriptionsCalc->sum('montant_net') > 0
                ? round(($inscriptionsCalc->sum('montant_paye_calc') / $inscriptionsCalc->sum('montant_net')) * 100, 1)
                : 0,
            'a_jour'    => $inscriptionsCalc->where('reste_calc', 0)->count(),
            'en_retard' => $inscriptionsCalc->where('reste_calc', '>', 0)->count(),
        ];

        $topDebiteurs = $inscriptionsCalc
            ->where('reste_calc', '>', 0)
            ->sortByDesc('reste_calc')
            ->take(20)
            ->values();

        return compact('annee', 'parClasse', 'totaux', 'topDebiteurs');
    }

    /**
     * Rapport mensuel : revenus + dépenses + résultat pour un mois donné.
     */
    public function mensuel(Etablissement $etab, ?string $mois = null): array
    {
        $mois = $mois ?: now()->format('Y-m');
        $debut = Carbon::parse($mois.'-01')->startOfMonth()->toDateString();
        $fin   = Carbon::parse($mois.'-01')->endOfMonth()->toDateString();

        return $this->produireRapportPeriode($etab, 'mensuel', $debut, $fin, [
            'libelle' => Carbon::parse($mois.'-01')->locale('fr')->isoFormat('MMMM YYYY'),
            'periode_id' => $mois,
        ]);
    }

    /**
     * Rapport trimestriel.
     * Trimestre = T1 (jan-mar), T2 (avr-juin), T3 (juil-sept), T4 (oct-déc).
     */
    public function trimestriel(Etablissement $etab, ?int $annee = null, ?int $trimestre = null): array
    {
        $annee = $annee ?: (int) now()->year;
        $trimestre = $trimestre ?: (int) ceil(now()->month / 3);

        $moisDebut = (($trimestre - 1) * 3) + 1;
        $debut = Carbon::create($annee, $moisDebut, 1)->startOfMonth()->toDateString();
        $fin   = Carbon::create($annee, $moisDebut + 2, 1)->endOfMonth()->toDateString();

        return $this->produireRapportPeriode($etab, 'trimestriel', $debut, $fin, [
            'libelle' => "T{$trimestre} {$annee}",
            'periode_id' => "{$annee}-T{$trimestre}",
        ]);
    }

    private function produireRapportPeriode(Etablissement $etab, string $type, string $debut, string $fin, array $meta): array
    {
        // Revenus = paiements confirmés
        $paiements = Paiement::where('etablissement_id', $etab->id)
            ->where('statut', 'confirme')
            ->whereBetween('date_paiement', [$debut, $fin])
            ->with('inscription.classe:id,nom')
            ->get();

        $totalRevenus    = (int) $paiements->sum('montant');
        $totalInscription = (int) $paiements->sum('montant_inscription');
        $totalScolarite  = (int) $paiements->sum('montant_scolarite');
        $nbPaiements     = $paiements->count();

        // Dépenses approuvées
        $depenses = Depense::where('etablissement_id', $etab->id)
            ->where('statut', 'approuvee')
            ->whereBetween('date_depense', [$debut, $fin])
            ->with('categorie:id,nom,couleur')
            ->get();

        $totalDepenses   = (int) $depenses->sum('montant');
        $nbDepenses      = $depenses->count();

        // Dépenses par catégorie
        $parCategorie = $depenses->groupBy('categorie_id')->map(fn($g) => [
            'categorie' => $g->first()->categorie?->nom ?? 'Non catégorisée',
            'couleur'   => $g->first()->categorie?->couleur ?? '#94a3b8',
            'nombre'    => $g->count(),
            'montant'   => (int) $g->sum('montant'),
        ])->sortByDesc('montant')->values();

        // Revenus par mode de paiement
        $parMode = $paiements->groupBy('mode')->map(fn($g) => [
            'mode'    => $g->first()->mode,
            'nombre'  => $g->count(),
            'montant' => (int) $g->sum('montant'),
        ])->sortByDesc('montant')->values();

        // Détail journalier
        $detailJournalier = collect();
        $current = Carbon::parse($debut);
        $end = Carbon::parse($fin);
        while ($current <= $end) {
            $date = $current->toDateString();
            $rev = (int) $paiements->where('date_paiement', $date)->sum('montant');
            $dep = (int) $depenses->where('date_depense', $date)->sum('montant');
            if ($rev > 0 || $dep > 0) {
                $detailJournalier->push([
                    'date'     => $current->format('d/m/Y'),
                    'revenus'  => $rev,
                    'depenses' => $dep,
                    'solde'    => $rev - $dep,
                ]);
            }
            $current->addDay();
        }

        $resultat = $totalRevenus - $totalDepenses;

        return [
            'type'             => $type,
            'libelle'          => $meta['libelle'],
            'periode_id'       => $meta['periode_id'],
            'debut'            => $debut,
            'fin'              => $fin,
            'totalRevenus'     => $totalRevenus,
            'totalInscription' => $totalInscription,
            'totalScolarite'   => $totalScolarite,
            'nbPaiements'      => $nbPaiements,
            'totalDepenses'    => $totalDepenses,
            'nbDepenses'       => $nbDepenses,
            'resultat'         => $resultat,
            'parCategorie'     => $parCategorie,
            'parMode'          => $parMode,
            'detailJournalier' => $detailJournalier,
        ];
    }
}
