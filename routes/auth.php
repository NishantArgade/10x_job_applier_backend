<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterUser;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::prefix('api/v1/auth')->group(function () {

    Route::prefix('{provider}')->middleware('guest')->group(function () {
        Route::get('redirect', [SocialAuthController::class, 'redirect']);
        Route::get('callback', [SocialAuthController::class, 'callback']);
    });

    Route::middleware('guest')->group(function () {
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
        Route::post('register', [RegisterUser::class, 'store']);
    });

    Route::middleware('auth')->post('logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::middleware('api')->get('/check-session', [AuthController::class, 'checkSession']);
});