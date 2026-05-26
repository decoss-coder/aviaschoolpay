<?php

use App\Http\Middleware\EnsureEleveStatutIsSet;
use Illuminate\Support\Facades\Route;

Route::get('eleves/{eleve}/statut-required', 'App\\Http\\Controllers\\EleveStatutRequiredController@edit')
    ->middleware(['auth', 'password.changed'])
    ->whereNumber('eleve')
    ->name('eleves.statut-required.edit');

Route::patch('eleves/{eleve}/statut-required', 'App\\Http\\Controllers\\EleveStatutRequiredController@update')
    ->middleware(['auth', 'password.changed'])
    ->whereNumber('eleve')
    ->name('eleves.statut-required.update');

Route::get('admin/rh/affectations/rapide', 'App\\Http\\Controllers\\Admin\\AffectationRapideController@create')
    ->middleware(['auth', 'password.changed', 'role:super_admin,directeur,directeur_adjoint,gestionnaire,secretaire,comptable,censeur'])
    ->name('admin.rh.affectations.rapide.create');

Route::post('admin/rh/affectations/rapide', 'App\\Http\\Controllers\\Admin\\AffectationRapideController@store')
    ->middleware(['auth', 'password.changed', 'role:super_admin,directeur,directeur_adjoint,gestionnaire,secretaire,comptable,censeur'])
    ->name('admin.rh.affectations.rapide.store');

Route::middleware(['auth', 'password.changed', 'role:super_admin,directeur,directeur_adjoint,gestionnaire,secretaire,comptable,censeur'])
    ->prefix('admin/rh')
    ->name('admin.rh.')
    ->group(function () {
        Route::get('/niveaux', 'App\\Http\\Controllers\\Admin\\NiveauxController@index')->name('niveaux.index');
        Route::post('/niveaux', 'App\\Http\\Controllers\\Admin\\NiveauxController@store')->name('niveaux.store');
        Route::patch('/niveaux/{niveau}', 'App\\Http\\Controllers\\Admin\\NiveauxController@update')->name('niveaux.update');
        Route::post('/niveaux/{niveau}/toggle', 'App\\Http\\Controllers\\Admin\\NiveauxController@toggle')->name('niveaux.toggle');
        Route::delete('/niveaux/{niveau}', 'App\\Http\\Controllers\\Admin\\NiveauxController@destroy')->name('niveaux.destroy');
    });

foreach ([
    'eleves.show',
    'finances.eleve',
    'finances.eleve.lien-wave',
    'paiements.create',
    'paiements.store',
] as $routeName) {
    $route = Route::getRoutes()->getByName($routeName);

    if ($route) {
        $route->middleware(EnsureEleveStatutIsSet::class);
    }
}
