<?php

use App\Http\Controllers\ResumeController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportJobApplications;
use App\Http\Controllers\ProcessJobApplication;

Route::get('/', function () {
    return view('welcome');
});

// middleware(['auth']) add later 
Route::prefix('api/v1')->group(function () {
    Route::post('/import-jobs', ImportJobApplications::class);
    Route::post('/process-jobs', ProcessJobApplication::class);
    Route::post('/template', [TemplateController::class, 'store']);
    Route::post('/resume', [ResumeController::class, 'store']);
});

