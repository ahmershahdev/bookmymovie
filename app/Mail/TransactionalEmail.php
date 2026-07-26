<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionalEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $subjectLine,
        public string $headline,
        public string $intro,
        public array $data = [],
        public ?string $actionText = null,
        public ?string $actionUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.transactional');
    }
}
