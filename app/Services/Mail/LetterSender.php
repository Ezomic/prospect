<?php

namespace App\Services\Mail;

use App\Enums\CompanyStatus;
use App\Enums\LetterStatus;
use App\Jobs\AppendLetterToSentFolder;
use App\Mail\OutreachMail;
use App\Models\Letter;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Mime\Email;

/**
 * Sends an open-aanbod letter as an email from the configured outreach
 * account, with the letter PDF and the user's CV attached. After sending it
 * marks the letter and company as sent, and queues the message to be filed in
 * the IMAP Sent folder.
 */
class LetterSender
{
    /**
     * Every reason a send can be refused, checked without doing any work, so
     * the request that queues a letter can answer immediately. The delivery
     * job runs it again: between queueing and running, the day's limit can
     * fill up or the company can be marked do not contact.
     */
    public function guard(Letter $letter, User $user): void
    {
        if ($letter->sent_at !== null) {
            throw new RuntimeException('This letter has already been sent.');
        }

        if (! in_array($letter->status, [LetterStatus::Ready, LetterStatus::Sending], true)) {
            throw new RuntimeException('Mark the letter as ready before sending it.');
        }

        $this->guardEnvironment();

        if ($letter->company->do_not_contact) {
            throw new RuntimeException('This company is marked do not contact.');
        }

        if ($letter->company->email === null) {
            throw new RuntimeException('The company has no email address.');
        }

        $this->guardDailyLimit();

        $this->cvPath($user);
    }

    private function cvPath(User $user): string
    {
        if ($user->cv_path === null || ! Storage::disk('local')->exists($user->cv_path)) {
            throw new RuntimeException('No CV is available to attach.');
        }

        return $user->cv_path;
    }

    /**
     * Renders and delivers the letter. Runs on the queue: dompdf, SMTP and the
     * Sent-folder append are all far too slow to sit in a web request.
     */
    public function deliver(Letter $letter, User $user): void
    {
        $this->guard($letter, $user);

        $pdf = Pdf::loadView('pdf.letter', ['letter' => $letter])->output();
        $cv = Storage::disk('local')->get($this->cvPath($user));

        $captured = null;

        $mail = (new OutreachMail($letter, $pdf, (string) $cv, $user->cv_original_name ?? 'cv.pdf'))
            ->withSymfonyMessage(function (Email $message) use (&$captured) {
                $captured = $message;
            });

        Mail::to($letter->company->email)->send($mail);

        $letter->forceFill([
            'status' => LetterStatus::Sent,
            'sent_at' => now(),
            'send_error' => null,
        ])->save();

        // Only ever advance a fresh company. A follow-up letter to a company
        // that already replied, bounced or was closed must not discard that
        // outcome, not least because ProcessIncomingMail keys off Sent.
        if ($letter->company->status === CompanyStatus::New) {
            $letter->company->update(['status' => CompanyStatus::Sent]);
        }

        if ($captured !== null) {
            AppendLetterToSentFolder::dispatch($this->stashRawMessage($letter, $captured->toString()));
        }
    }

    /**
     * Outside production a send is refused unless deliberately opted into, so
     * a local or staging run can never put mail in a real company's inbox.
     */
    private function guardEnvironment(): void
    {
        if (app()->environment('production') || config('outreach.allow_send') === true) {
            return;
        }

        throw new RuntimeException(
            'Sending is disabled outside production. Set OUTREACH_ALLOW_SEND=true to send from this environment.'
        );
    }

    private function guardDailyLimit(): void
    {
        $limit = config('outreach.daily_send_limit');

        if (! is_int($limit) || $limit <= 0) {
            return;
        }

        $sentToday = Letter::query()->whereDate('sent_at', today())->count();

        if ($sentToday >= $limit) {
            throw new RuntimeException("The daily send limit of {$limit} letters has been reached.");
        }
    }

    /**
     * The raw MIME can carry megabytes of attachments, so it goes to disk and
     * the job carries only the path.
     */
    private function stashRawMessage(Letter $letter, string $rawMessage): string
    {
        $path = "outreach-sent/{$letter->id}-".Str::uuid()->toString().'.eml';

        Storage::disk('local')->put($path, $rawMessage);

        return $path;
    }
}
