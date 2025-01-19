<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ImportJobApplications;
use App\Http\Controllers\ProcessJobApplication;

require __DIR__.'/auth.php';

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->prefix('api/v1')->group(function () {

    Route::middleware('permission:dashboard')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    Route::middleware('permission:manage.applications')->group(function () {
        Route::post('/jobs/import', ImportJobApplications::class);
        Route::post('/jobs/process', ProcessJobApplication::class);
    });

    Route::middleware('permission:manage.templates')->group(function () {
        Route::get('/templates', [TemplateController::class, 'index']);
        Route::post('/template', [TemplateController::class, 'store']);
    });

    Route::middleware('permission:manage.resumes')->group(function () {
        Route::get('/resumes', [ResumeController::class, 'index']);
        Route::post('/resume', [ResumeController::class, 'store']);
    });
    
    Route::middleware('permission:manage.users')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
    });
    
});
