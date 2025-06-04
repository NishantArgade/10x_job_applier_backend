<?php

use App\Models\User;
use App\Models\Resume;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobApplicationContoller;
use App\Http\Controllers\NaukriBotController;

// Auth Routes
require __DIR__.'/auth.php';

Route::get('/', function () {
    return view('welcome');
});

// Sample CSV Download Route - publicly accessible
Route::get('/api/v1/sample-jobs-csv', function () {
    $filePath = public_path('sample_jobs.csv');
    return response()->download($filePath, 'sample_jobs_template.csv', [
        'Content-Type' => 'text/csv',
    ]);
});

// Public Resume PDF Access Route (No Authentication Required)
Route::get('/api/v1/public-resume/{uuid}', function ($uuid) {
    $resume = Resume::where('uuid', $uuid)->firstOrFail();
    $path = storage_path('app/public/'.$resume->path);

    if (! file_exists($path)) {
        abort(404, 'Resume file not found');
    }

    // Use original filename if available, otherwise use the stored filename
    $displayFilename = $resume->original_filename ?? $resume->file_name;

    return response()->file($path, [
        'Content-Type' => $resume->mime_type,
        'Content-Disposition' => 'inline; filename="'.$displayFilename.'"',
        'Cache-Control' => 'public, max-age=86400',
        'Access-Control-Allow-Origin' => '*',
    ]);
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

Route::get('/bot/start-update-profile', [NaukriBotController::class, 'startProfileUpdate']);
Route::get('/bot/start-apply-jobs', [NaukriBotController::class, 'startApplyJobs']);
Route::get('/bot/stop', [NaukriBotController::class, 'stop']);
