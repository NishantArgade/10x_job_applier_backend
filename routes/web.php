<?php

use App\Http\Controllers\ApplicationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// middleware(['auth']) add later 
Route::prefix('dash/page')->group(function () {
    Route::post('/import-applications', [ApplicationController::class, 'index']);

});

