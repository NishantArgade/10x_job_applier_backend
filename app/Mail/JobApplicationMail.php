<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;

class JobApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $templateData = [];

    public function __construct(array $templateData)
    {
        $this->templateData = $templateData;
    }

    public function envelope(): Envelope
    {
        return new Envelope(

            subject: $this->templateData['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->templateData['view'],
            with: $this->templateData['data'],
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        $attachmentPath = $this->templateData['attachmentPath'] ?? null;

        if ($attachmentPath && file_exists($attachmentPath)) {
            $attachments[] = Attachment::fromPath($attachmentPath)
                ->as(basename($attachmentPath))
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
