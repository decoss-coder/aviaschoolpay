<?php

namespace App\Observers;

use App\Models\Depense;
use App\Services\Budget\BudgetAlimentationService;

class DepenseObserver
{
    public function updated(Depense $depense): void
    {
        if ($depense->wasChanged('statut') && $depense->statut === 'approuvee') {
            app(BudgetAlimentationService::class)->alimenterDepuisDepense($depense);
        }
    }
}
