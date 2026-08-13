<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $digest
     */
    public function __construct(public array $digest) {}

    public function envelope(): Envelope
    {
        $quiet = ($this->digest['quiet'] ?? false) === true;

        return new Envelope(
            subject: $quiet
                ? 'Prospect: niets verstuurd deze week'
                : 'Prospect: weekoverzicht',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.weekly-digest', with: ['digest' => $this->digest]);
    }
}
