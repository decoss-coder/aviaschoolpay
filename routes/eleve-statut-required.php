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
