<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkflowNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $mailSubject,
        private readonly string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.workflow-notification', with: ['body' => $this->body]);
    }
}
