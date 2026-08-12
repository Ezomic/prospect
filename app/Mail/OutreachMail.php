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
     */
    public function __construct(
        public Letter $letter,
        public string $pdf,
    ) {}

    /**
     * The delivered subject, body and attachment name are read through these
     * three so a preview cannot drift from what is actually sent. Anything
     * describing the mail to the user must go through them too.
     */
    public static function subjectFor(Letter $letter): string
    {
        return $letter->email_subject ?? $letter->subject;
    }

    public static function bodyFor(Letter $letter): string
    {
        return $letter->email_body ?? '';
    }

    public static function attachmentNameFor(Letter $letter): string
    {
        return "brief-{$letter->id}.pdf";
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::subjectFor($this->letter));
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.outreach',
            with: ['body' => self::bodyFor($this->letter)],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, self::attachmentNameFor($this->letter))
                ->withMime('application/pdf'),
        ];
    }
}
