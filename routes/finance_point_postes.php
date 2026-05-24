<?php

use App\Http\Controllers\FinancePointPostesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'password.changed'])
    ->prefix('finances/point-postes')
    ->name('finances.point-postes.')
    ->group(function () {
        Route::get('/', [FinancePointPostesController::class, 'index'])->name('index');
    });
