<?php

namespace App\Observers;

use App\Models\EcritureComptable;
use App\Models\Paiement;
use App\Services\Budget\BudgetAlimentationService;
use App\Services\Comptabilite\ComptabilisationService;
use App\Services\Notifications\PaiementNotificationService;

class PaiementObserver
{
    public function created(Paiement $paiement): void
    {
        if ($paiement->statut === 'en_attente') {
            PaiementNotificationService::notifierInitie($paiement);
        }

        if ($paiement->statut === 'confirme') {
            $this->onConfirme($paiement);
        }
    }

    public function updated(Paiement $paiement): void
    {
        if ($paiement->wasChanged('statut') && $paiement->statut === 'confirme') {
            $this->onConfirme($paiement);
        }
    }

    private function onConfirme(Paiement $paiement): void
    {
        PaiementNotificationService::notifierConfirme($paiement);

        app(ComptabilisationService::class)->comptabiliserPaiement(
            $paiement,
            $paiement->encaisse_par ?? auth()->id() ?? 0
        );

        // Alimentation auto du suivi budgétaire (montant_reel des lignes revenu)
        app(BudgetAlimentationService::class)->alimenterDepuisPaiement($paiement);
    }
}
