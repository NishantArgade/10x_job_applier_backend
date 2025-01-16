<?php

namespace App\Http\Controllers;

use Throwable;
use Illuminate\Bus\Batch;
use App\Models\Application;
use App\Jobs\SendApplicationEmail;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ProcessJobApplication
{
    const LOG_CHANNEL = 'job_application';

    public function __invoke()
    {
        Log::channel(self::LOG_CHANNEL)->info('Processing job applications.');

        $applications = Application::query()
            ->with(['template', 'resume'])
            ->where('status', 'pending')
            ->whereDate('apply_at', '<=', now())
            ->whereNull('processed_at')
            ->get();

        if ($applications->isEmpty()) {
            Log::channel(self::LOG_CHANNEL)->info('No applications to process.');
            return 'No applications to process.';
        }

        $applications->toQuery()->update(['processed_at' => now()]);

        $jobs = $applications->map(function (Application $application) {
            return new SendApplicationEmail($application->id);
        });

        return Bus::batch($jobs)
            ->allowFailures()
            ->then(function (Batch $batch) {
                Log::channel(self::LOG_CHANNEL)->info("Batch {$batch->id} completed successfully.");
            })
            ->catch(function (Batch $batch, Throwable $e) {
                Log::channel(self::LOG_CHANNEL)->error("Batch {$batch->id} failed.", ['error' => $e->getMessage()]);
            })
            ->name('Process Job Applications')
            ->dispatch();
    }
}
