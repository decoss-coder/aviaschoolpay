<?php

use App\Http\Controllers\SchoolAccessControlController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'password.changed'])
    ->prefix('controle-acces')
    ->name('access-control.')
    ->group(function () {
        Route::get('/', [SchoolAccessControlController::class, 'index'])->name('index');
        Route::post('/', [SchoolAccessControlController::class, 'update'])->name('update');
    });
