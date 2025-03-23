<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobApplicationContoller;

// Auth Routes
require __DIR__.'/auth.php';

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->prefix('api/v1')->group(function () {

    // Dashboard
    Route::middleware('permission:dashboard')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // Job Applications
    Route::middleware('permission:manage.applications')->group(function () {
        Route::post('/jobs/import', [JobApplicationContoller::class, 'import']);
        Route::post('/jobs/process', [JobApplicationContoller::class, 'process']);
        Route::get('/jobs', [JobApplicationContoller::class, 'index']);
        Route::get('/jobs/{application}', [JobApplicationContoller::class, 'single']);
        Route::put('/jobs/{application}', [JobApplicationContoller::class, 'update']);
        Route::delete('/jobs/{application}', [JobApplicationContoller::class, 'delete']);
    });

    // Templates
    Route::middleware('permission:manage.templates')->group(function () {
        Route::get('/templates', [TemplateController::class, 'index']);
        Route::post('/template', [TemplateController::class, 'store']);
        Route::put('/template/{template}', [TemplateController::class, 'update']);
        Route::delete('/template/{template}', [TemplateController::class, 'destroy']);
    });

    // Resumes
    Route::middleware('permission:manage.resumes')->group(function () {
        Route::get('/resumes', [ResumeController::class, 'index']);
        Route::post('/resume', [ResumeController::class, 'store']);
        Route::put('/resume/{resume}', [ResumeController::class, 'update']);
        Route::delete('/resume/{resume}', [ResumeController::class, 'destroy']);
    });

    // Profile
    Route::middleware('permission:manage.profile')->group(function () {
        Route::get('/profile', [ProfileController::class, 'index']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::delete('/profile', [ProfileController::class, 'destroy']);
    });

    // Users
    Route::middleware('permission:manage.users')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
    });

});
