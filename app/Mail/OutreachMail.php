<?php

namespace App\Mail;

use App\Models\Letter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $pdf  the rendered letter PDF bytes
     * @param  string  $cv  the CV PDF bytes
     */
    public function __construct(
        public Letter $letter,
        public string $pdf,
        public string $cv,
        public string $cvName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->letter->email_subject ?? $this->letter->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.outreach',
            with: ['body' => $this->letter->email_body ?? ''],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, "brief-{$this->letter->id}.pdf")
                ->withMime('application/pdf'),
            Attachment::fromData(fn () => $this->cv, $this->cvName)
                ->withMime('application/pdf'),
        ];
    }
}
