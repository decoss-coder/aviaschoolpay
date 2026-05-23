<?php

namespace App\Services\Budget;

use App\Models\Budget;
use App\Models\Depense;
use App\Models\ExerciceComptable;
use App\Models\LigneBudgetaire;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;

/**
 * Alimente automatiquement les lignes budgétaires (montant_reel)
 * à partir des paiements confirmés et dépenses approuvées.
 *
 * Logique de rapprochement :
 *  - Paiement → ligne REVENU dont compte_comptable_numero correspond au compte produit (706100/706200)
 *  - Dépense  → ligne DEPENSE dont categorie_depense_id match, sinon dont compte_comptable_numero match
 */
class BudgetAlimentationService
{
    /**
     * Alimente les lignes budgétaires à partir d'un paiement confirmé.
     * Idempotent : ne double pas l'incrémentation si appelé plusieurs fois.
     */
    public function alimenterDepuisPaiement(Paiement $paiement): int
    {
        if ($paiement->statut !== 'confirme') {
            return 0;
        }

        $budget = $this->budgetActif($paiement->etablissement_id, $paiement->date_paiement);
        if (! $budget) return 0;

        $montantInscription = (int) ($paiement->montant_inscription ?? 0);
        $montantScolarite   = (int) ($paiement->montant_scolarite ?? 0);

        // Fallback : si pas de ventilation, tout passe en scolarité
        if ($montantInscription <= 0 && $montantScolarite <= 0) {
            $montantScolarite = (int) $paiement->montant;
        }

        $updated = 0;

        if ($montantInscription > 0) {
            $updated += $this->incrementerLigne($budget, 'revenu', '706200', $montantInscription);
        }
        if ($montantScolarite > 0) {
            $updated += $this->incrementerLigne($budget, 'revenu', '706100', $montantScolarite);
        }

        if ($updated > 0) {
            $this->recalculerTotaux($budget);
        }

        return $updated;
    }

    /**
     * Alimente les lignes budgétaires à partir d'une dépense approuvée.
     */
    public function alimenterDepuisDepense(Depense $depense): int
    {
        if ($depense->statut !== 'approuvee') {
            return 0;
        }

        $budget = $this->budgetActif($depense->etablissement_id, $depense->date_depense);
        if (! $budget) return 0;

        $depense->loadMissing('categorie');

        $ligne = LigneBudgetaire::where('budget_id', $budget->id)
            ->where('type', 'depense')
            ->where(function ($q) use ($depense) {
                $q->where('categorie_depense_id', $depense->categorie_id);
                if ($num = $depense->categorie?->compte_comptable_numero) {
                    $q->orWhere('compte_comptable_numero', $num);
                }
            })
            ->first();

        if (! $ligne) return 0;

        $ligne->montant_reel += (int) $depense->montant;
        $ligne->recalculer();

        $this->recalculerTotaux($budget);
        return 1;
    }

    /**
     * Reconstruit intégralement le réel d'un budget en rejouant
     * tous les paiements/dépenses de son exercice.
     * Utile pour les budgets créés après-coup ou en cas de désynchro.
     */
    public function recalculerBudget(Budget $budget): array
    {
        return DB::transaction(function () use ($budget) {
            // Reset
            LigneBudgetaire::where('budget_id', $budget->id)->update([
                'montant_reel' => 0,
                'ecart' => DB::raw('-montant_prevu'),
                'taux_realisation' => 0,
                'alerte_depassement' => false,
            ]);

            $exerciceId = $budget->exercice_id;
            $exercice = ExerciceComptable::find($exerciceId);
            $debut = $exercice?->date_debut;
            $fin   = $exercice?->date_fin;

            // Rejouer paiements
            $nbPaiements = 0;
            $paiements = Paiement::where('etablissement_id', $budget->etablissement_id)
                ->where('statut', 'confirme')
                ->when($debut, fn($q) => $q->where('date_paiement', '>=', $debut))
                ->when($fin,   fn($q) => $q->where('date_paiement', '<=', $fin))
                ->cursor();

            foreach ($paiements as $p) {
                $nbPaiements += $this->alimenterDepuisPaiement($p);
            }

            // Rejouer dépenses
            $nbDepenses = 0;
            $depenses = Depense::where('etablissement_id', $budget->etablissement_id)
                ->where('statut', 'approuvee')
                ->where('exercice_id', $exerciceId)
                ->with('categorie')
                ->cursor();

            foreach ($depenses as $d) {
                $nbDepenses += $this->alimenterDepuisDepense($d);
            }

            $this->recalculerTotaux($budget->fresh());

            return ['paiements' => $nbPaiements, 'depenses' => $nbDepenses];
        });
    }

    /**
     * Trouve le budget actif (validé ou en_cours) à une date donnée.
     */
    private function budgetActif(int $etablissementId, $date = null): ?Budget
    {
        $date = $date ?: now();
        $exercice = ExerciceComptable::where('etablissement_id', $etablissementId)
            ->where('date_debut', '<=', $date)
            ->where('date_fin', '>=', $date)
            ->first();

        if (! $exercice) {
            $exercice = ExerciceComptable::where('etablissement_id', $etablissementId)
                ->where('en_cours', true)
                ->first();
        }

        if (! $exercice) return null;

        return Budget::where('etablissement_id', $etablissementId)
            ->where('exercice_id', $exercice->id)
            ->whereIn('statut', ['valide', 'en_cours'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Cherche une ligne par type + compte comptable et incrémente son réel.
     */
    private function incrementerLigne(Budget $budget, string $type, string $numeroCompte, int $montant): int
    {
        $ligne = LigneBudgetaire::where('budget_id', $budget->id)
            ->where('type', $type)
            ->where('compte_comptable_numero', $numeroCompte)
            ->first();

        if (! $ligne) return 0;

        $ligne->montant_reel += $montant;
        $ligne->recalculer();
        return 1;
    }

    private function recalculerTotaux(Budget $budget): void
    {
        $budget->loadMissing('lignes');
        $budget->update([
            'total_reel_revenus'  => $budget->lignes->where('type', 'revenu')->sum('montant_reel'),
            'total_reel_depenses' => $budget->lignes->where('type', 'depense')->sum('montant_reel'),
        ]);
    }
}
