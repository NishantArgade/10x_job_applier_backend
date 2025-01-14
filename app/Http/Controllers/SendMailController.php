<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\JobApplicationMail;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SendMailController extends Controller
{
    public function index(Request $request)
    {
        $recipientMail = 'nishantargade4579@gmail.com';
        $attachmentPath = storage_path('app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resumes' . DIRECTORY_SEPARATOR . 'Nishant_Argade_Resume.pdf');
        $templateId = 1;

        $template = Template::findOrFail($templateId);

        // Replace variables in the template body
        $variables = [
            '{{name}}' => 'John Doe',
            '{{job_title}}' => 'Software Developer',
            '{{company_name}}' => 'Your Company Name',
        ];

        $emailBody = str_replace(
            array_keys($variables),
            array_values($variables),
            $template->body
        );
        

        // Send the email
        Mail::to($recipientMail)->send(new JobApplicationMail([
            'view' => 'emails.dynamic_template',
            'data' => [
                'content' => $emailBody,
            ],
            'subject' => $template->subject,
            'attachmentPath' => $attachmentPath,
        ]));

        return  'Email sent successfully';

    }
}
