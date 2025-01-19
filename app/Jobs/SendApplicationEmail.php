<?php

namespace App\Jobs;

use App\Models\Application;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Mail\JobApplicationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendApplicationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    const LOG_CHANNEL = 'job_application';

    public function __construct(private int $applicationId)
    {
    }

    public function handle(): void
    {
        $application = Application::find($this->applicationId);

        if (! $application) {
            Log::channel(self::LOG_CHANNEL)->error("Application ID: {$this->applicationId} not found.");
            return;
        }

        try {
            $jobMail = new JobApplicationMail(
                [
                    'view' => 'emails.dynamic_template',
                    'data' => [
                        'content' => $this->getTemplateBody($application->template->body),
                    ],
                    'subject' => $application->template->subject,
                    'attachmentPath' => storage_path($application->resume->path),
                ]
            );

            if (! app()->isProduction() && env('MAIL_MAILER') === 'log') {
                Log::channel('email')->info(sprintf(
                    "Email Content:\nFrom: %s\nSubject: %s\nTo: %s\nContent: %s\nAttachment: %s",
                    config('mail.from.address'),
                    $application->template->subject,
                    $application->email,
                    $this->getTemplateBody($application->template->body),
                    storage_path($application->resume->path)
                ));

                Log::channel('email')->info(" ");
                $application->update(['status' => 'sent']);
                return;
            }

            Mail::to($application->email)->send($jobMail);
            $application->update(['status' => 'sent']);

        } catch (\Throwable $e) {
            $application->update(['status' => 'failed']);
            Log::channel(self::LOG_CHANNEL)->error("Failed to send email to {$application->email} for Application ID: {$this->applicationId}", [
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function getTemplateBody($body)
    {
        $variables = [
            '{{name}}' => 'John Doe',
            '{{job_title}}' => 'Software Developer',
            '{{company_name}}' => 'Your Company Name',
        ];

        return str_replace(
            array_keys($variables),
            array_values($variables),
            $body
        );
    }
}
