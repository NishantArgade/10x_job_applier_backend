<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterUser;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::prefix('api/v1')
    ->middleware('api')
    ->group(function () {

        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('guest')
            ->name('login');

        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
            ->middleware('auth')
            ->name('logout');

        Route::post('/register', [RegisterUser::class, 'store'])
            ->middleware('guest')
            ->name('register');
    });
