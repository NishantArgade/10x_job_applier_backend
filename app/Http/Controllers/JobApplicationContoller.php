<?php

namespace App\Http\Controllers;

use Throwable;
use Illuminate\Bus\Batch;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Jobs\SendApplicationEmail;
use App\Imports\ApplicationsImport;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class JobApplicationContoller extends Controller
{
    const LOG_CHANNEL = 'job_application';

    public function index(Request $request)
    {
        //@todo: add spatie filter
        
        $applications = Application::query()
            ->with(['template', 'resume'])
            ->paginate(10);

        return $applications->through(function ($application) {
            return [
                'id' => $application->id,
                'apply_for' => $application->apply_for,
                'name' => $application->name,
                'email' => $application->email,
                'phone' => $application->phone,
                'company' => $application->company,
                'location' => $application->location ?? 'N/A',
                'apply_at' => $application->apply_at->format('Y-m-d'),
                'status' => $application->status,
                'source' => $application->source ?? 'N/A',
                'website' => $application->website ?? 'N/A',
                'followup_after_days' => $application->followup_after_days,
                'followup_freq' => $application->followup_freq,
                'resume' => $application->resume?->download_url ?? null,
                'resume_name' => $application->resume?->original_filename ?? 'N/A',
                'template' => $application->template?->name ?? 'N/A',
            ];
        });
    }

    // method: single, edit, delete

    public function single(Request $request, Application $application)
    {
        $application->load(['template', 'resume']);

        return $application;
    }

    public function update(Request $request, Application $application)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'company' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'website' => 'nullable|url',
            'location' => 'nullable|string',
            'source' => 'nullable|string',
            'apply_at' => 'nullable|date',
            'apply_for' => 'required|string',
            'followup_after_days' => 'required|integer',
            'followup_freq' => 'required|integer',
        ]);

        $application->update([
            'name' => $validated['name'],
            'company' => $validated['company'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'website' => $validated['website'],
            'location' => $validated['location'],
            'source' => $validated['source'],
            'apply_for' => $validated['apply_for'],
            'apply_at' => $validated['apply_at'],
            'followup_after_days' => $validated['followup_after_days'],
            'followup_freq' => $validated['followup_freq'],
        ]);

        return response()->json([
            'message' => 'Application updated successfully!',
            'application' => $application,
        ]);
    }

    public function delete(Request $request, Application $application)
    {
        $application->delete();

        return response()->json([
            'message' => 'Application deleted successfully!',
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'applications_csv' => 'required|mimes:csv,txt',
            'template_id' => 'required|exists:templates,id',
            'resume_id' => 'required|exists:resumes,id',
        ]);

        Excel::import(
            new ApplicationsImport($validated['template_id'], $validated['resume_id']),
            $validated['applications_csv']
        );

        return response()->json([
            'message' => 'Applications imported successfully!',
        ]);
    }

    public function process()
    {
        Log::channel(self::LOG_CHANNEL)->info('Processing job applications.');

        $applications = Application::query()
            ->with(['template', 'resume'])
            ->where('status', 'pending')
            // ->whereNull('processed_at')
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
