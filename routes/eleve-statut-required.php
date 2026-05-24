<?php

use Illuminate\Support\Facades\Route;

Route::get('eleves/{eleve}/statut-required', 'App\\Http\\Controllers\\EleveStatutRequiredController@edit')
    ->middleware(['auth'])
    ->whereNumber('eleve')
    ->name('eleves.statut-required.edit');

Route::patch('eleves/{eleve}/statut-required', 'App\\Http\\Controllers\\EleveStatutRequiredController@update')
    ->middleware(['auth'])
    ->whereNumber('eleve')
    ->name('eleves.statut-required.update');
