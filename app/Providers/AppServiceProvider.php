<?php

namespace App\Providers;

use App\Models\Depense;
use App\Models\Eleve;
use App\Models\Paiement;
use App\Observers\DepenseObserver;
use App\Observers\PaiementObserver;
use App\Policies\ElevePolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/school_access.php'));
        $this->loadRoutesFrom(base_path('routes/finance_impayes.php'));

        Gate::policy(Eleve::class, ElevePolicy::class);
        Paiement::observe(PaiementObserver::class);
        Depense::observe(DepenseObserver::class);

        // ─── Directives Blade pour le mode lecture seule ───
        // Usage : @lectureSeule ... @endlectureSeule  /  @editable ... @endeditable
        Blade::if('lectureSeule', function () {
            $annee = \App\Services\Scolarite\AnneeScolaireContext::courante();

            return $annee && $annee->estLectureSeule();
        });

        Blade::if('editable', function () {
            $annee = \App\Services\Scolarite\AnneeScolaireContext::courante();

            return ! $annee || ! $annee->estLectureSeule();
        });

        Gate::before(function ($user, string $ability) {
            if (! in_array($ability, ['create', 'update', 'delete', 'restore', 'forceDelete'], true)) {
                return null;
            }

            $annee = \App\Services\Scolarite\AnneeScolaireContext::courante();
            if ($annee?->estLectureSeule()) {
                return false;
            }

            return null;
        });
    }
}
