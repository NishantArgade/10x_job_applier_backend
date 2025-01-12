<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return response()->json(['message' => 'Hello from Laravel 🚀!']);
});
// Route::get('/users', [UserController::class, 'index']);
// Route::post('/users', [UserController::class, 'store']);