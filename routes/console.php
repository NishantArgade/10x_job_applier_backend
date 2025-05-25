<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\JobApplicationContoller;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(JobApplicationContoller::class)->everyThirtyMinutes();

Schedule::command('naukri:bot')->hourly()->withoutOverlapping();
