<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterUser;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::prefix('api/v1/auth')->group(function () {

    // Social Auth
    Route::prefix('{provider}')->middleware('guest')->group(function () {
        Route::get('redirect', [SocialAuthController::class, 'redirect']);
        Route::get('callback', [SocialAuthController::class, 'callback']);
    });

    // Email Auth
    Route::middleware('guest')->group(function () {
        Route::post('login', [AuthenticatedSessionController::class, 'store']);
        Route::post('register', [RegisterUser::class, 'store']);
    });

    // Logout
    Route::middleware('auth')->post('logout', [AuthenticatedSessionController::class, 'destroy']);

    // Check Session
    Route::middleware('api')->get('/check-session', [AuthController::class, 'checkSession']);

    // create temp auth session
    Route::get('/temp', function () {
        Auth::login(User::find(1));
        return response()->json([
            'message' => 'Temp session created',
            'user' => Auth::user()
        ]);
    });

});