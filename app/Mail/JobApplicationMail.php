<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $templateData;

    /**
     * Create a new message instance.
     *
     * @param array $templateData
     */
    public function __construct($templateData)
    {
        $this->templateData = $templateData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            
            subject: $this->templateData['subject'], // Use dynamic subject
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->templateData['view'], // Use dynamic view
            with: $this->templateData['data'], // Pass dynamic data to the view
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        $attachmentPath = $this->templateData['attachmentPath'] ?? null;

        if ($attachmentPath && file_exists($attachmentPath)) {
            $attachments[] = Attachment::fromPath($attachmentPath)
                ->as(basename($attachmentPath)) // Optionally rename the file
                ->withMime('application/pdf'); // Specify the MIME type
        }

        return $attachments;
    }
}
