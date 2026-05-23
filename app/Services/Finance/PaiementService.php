<?php

namespace App\Services\Finance;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Services\Eleve\EleveScolariteService;
use App\Services\Scolarite\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaiementService
{
    /** @return array{inscription: array, scolarite: array, total: array} */
    public static function grilleDepuisResume(array $resume): array
    {
        $totalIns = 0;
        $totalScol = 0;
        $totalPaye = 0;
        $libelleIns = 'Frais d\'inscription';

        foreach ($resume['inscriptions'] ?? [] as $insc) {
            $totalIns += (int) ($insc['montant_inscription'] ?? 0);
            $totalScol += (int) ($insc['montant_scolarite'] ?? 0);
            $totalPaye += (int) ($insc['montant_paye'] ?? 0);
            $libelleIns = $insc['libelle_inscription'] ?? $libelleIns;
        }

        $payeIns = min($totalPaye, $totalIns);
        $payeScol = min(max(0, $totalPaye - $totalIns), $totalScol);

        return [
            'inscription' => [
                'libelle' => $libelleIns,
                'montant' => $totalIns,
                'paye' => $payeIns,
                'reste' => max(0, $totalIns - $payeIns),
                'applicable' => $totalIns > 0,
            ],
            'scolarite' => [
                'libelle' => 'Scolarité annuelle',
                'montant' => $totalScol,
                'paye' => $payeScol,
                'reste' => max(0, $totalScol - $payeScol),
                'applicable' => ($resume['scolarite_applicable'] ?? false) && $totalScol > 0,
            ],
            'total' => [
                'montant' => $totalIns + $totalScol,
                'paye' => $totalPaye,
                'reste' => max(0, ($totalIns + $totalScol) - $totalPaye),
            ],
        ];
    }

    /** Répartition d'un montant sur inscription puis scolarité. */
    public static function repartirMontant(array $grille, int $montant, string $posteCible = 'auto'): array
    {
        $resteIns = (int) ($grille['inscription']['reste'] ?? 0);
        $resteScol = (int) ($grille['scolarite']['reste'] ?? 0);

        if ($posteCible === 'inscription') {
            $partIns = min($montant, $resteIns);
            $partScol = 0;
        } elseif ($posteCible === 'scolarite') {
            $partIns = 0;
            $partScol = min($montant, $resteScol);
        } else {
            $partIns = min($montant, $resteIns);
            $partScol = min($montant - $partIns, $resteScol);
        }

        return [
            'montant_inscription' => $partIns,
            'montant_scolarite' => $partScol,
        ];
    }

    public static function resolveEtablissement(Request $request): Etablissement
    {
        $etabId = $request->user()->ecoleActiveId();
        $etab = $etabId
            ? Etablissement::find($etabId)
            : $request->user()->etablissement;

        abort_unless($etab, 403, 'Aucun établissement associé.');

        return $etab;
    }

    public static function resolveAnneeCourante(Etablissement $etab): ?AnneeScolaire
    {
        $ctx = AnneeScolaireContext::courante();
        if ($ctx && $ctx->etablissement_id === $etab->id) {
            return $ctx;
        }

        return $etab->anneesScolaires()->where('en_cours', true)->where('cloturee', false)->first();
    }

    public static function resolveInscription(Etablissement $etab, AnneeScolaire $annee, Eleve $eleve): ?Inscription
    {
        $inscription = Inscription::query()
            ->where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $annee->id)
            ->where('etablissement_id', $etab->id)
            ->where('statut', 'validee')
            ->latest('date_inscription')
            ->first();

        if ($inscription) {
            return TarificationService::synchroniserInscription($inscription, $eleve);
        }

        $eleve->loadMissing('classe.niveau');
        if (! $eleve->classe_id) {
            return null;
        }

        $draft = new Inscription([
            'eleve_id' => $eleve->id,
            'classe_id' => $eleve->classe_id,
            'annee_scolaire_id' => $annee->id,
            'etablissement_id' => $etab->id,
            'date_inscription' => now()->toDateString(),
            'type' => 'nouvelle',
            'statut' => 'validee',
            'reduction' => 0,
        ]);
        $draft->setRelation('classe', $eleve->classe);

        $du = EleveScolariteService::montantsDu($draft, $eleve);

        $inscription = Inscription::create([
            'eleve_id' => $eleve->id,
            'classe_id' => $eleve->classe_id,
            'annee_scolaire_id' => $annee->id,
            'etablissement_id' => $etab->id,
            'date_inscription' => now()->toDateString(),
            'type' => 'nouvelle',
            'statut' => 'validee',
            'montant_inscription' => $du['montant_inscription'],
            'montant_scolarite' => $du['montant_scolarite'],
            'montant_net' => $du['montant_total_du'],
            'reduction' => 0,
        ]);

        return $inscription->fresh(['classe.niveau']);
    }

    /**
     * @return array{total_inscription: int, total_scolarite: int, paye_inscription: int, paye_scolarite: int, reste_inscription: int, reste_scolarite: int}
     */
    public static function recouvrementParPostes(int $etablissementId, int $anneeScolaireId): array
    {
        $totals = [
            'total_inscription' => 0, 'total_scolarite' => 0,
            'paye_inscription' => 0, 'paye_scolarite' => 0,
            'reste_inscription' => 0, 'reste_scolarite' => 0,
        ];

        $inscriptions = Inscription::query()
            ->where('etablissement_id', $etablissementId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', 'validee')
            ->with('eleve')
            ->get();

        foreach ($inscriptions as $inscription) {
            if (! $inscription->eleve) {
                continue;
            }
            $resume = EleveScolariteService::resumePourEleve($inscription->eleve, $anneeScolaireId);
            $grille = self::grilleDepuisResume($resume);
            $totals['total_inscription'] += $grille['inscription']['montant'];
            $totals['total_scolarite'] += $grille['scolarite']['montant'];
            $totals['paye_inscription'] += $grille['inscription']['paye'];
            $totals['paye_scolarite'] += $grille['scolarite']['paye'];
            $totals['reste_inscription'] += $grille['inscription']['reste'];
            $totals['reste_scolarite'] += $grille['scolarite']['reste'];
        }

        return $totals;
    }

    public static function canalDepuisMode(string $mode): string
    {
        return $mode === 'wave' ? 'wave' : 'manuel';
    }

    public static function genererNumeroRecu(int $etablissementId): string
    {
        $rang = Paiement::where('etablissement_id', $etablissementId)
            ->whereNotNull('numero_recu')
            ->whereYear('date_paiement', now()->year)
            ->whereMonth('date_paiement', now()->month)
            ->count() + 1;

        return sprintf('REC-%s-%04d', now()->format('Ym'), $rang);
    }

    public static function creerPaiementManuelConfirme(
        Etablissement $etab,
        Eleve $eleve,
        Inscription $inscription,
        int $montant,
        string $mode,
        string $posteCible,
        array $repartition,
        int $encaissePar,
        ?string $datePaiement = null,
        ?string $observations = null
    ): Paiement {
        return DB::transaction(function () use (
            $etab, $eleve, $inscription, $montant, $mode, $posteCible,
            $repartition, $encaissePar, $datePaiement, $observations
        ) {
            return Paiement::create([
                'etablissement_id' => $etab->id,
                'inscription_id' => $inscription->id,
                'eleve_id' => $eleve->id,
                'encaisse_par' => $encaissePar,
                'reference' => Paiement::genererReference($etab->id),
                'montant' => $montant,
                'montant_inscription' => $repartition['montant_inscription'],
                'montant_scolarite' => $repartition['montant_scolarite'],
                'poste_cible' => $posteCible,
                'canal_paiement' => self::canalDepuisMode($mode),
                'date_paiement' => $datePaiement ?? today()->toDateString(),
                'date_validation' => now(),
                'mode' => $mode,
                'statut' => 'confirme',
                'numero_recu' => self::genererNumeroRecu($etab->id),
                'observations' => $observations,
            ]);
        });
    }
}
