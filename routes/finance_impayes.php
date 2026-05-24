<?php

use App\Http\Controllers\FinanceImpayesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'password.changed'])
    ->prefix('finances/impayes')
    ->name('finances.impayes.')
    ->group(function () {
        Route::get('/', [FinanceImpayesController::class, 'index'])->name('index');
    });
