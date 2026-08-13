<?php

namespace App\Jobs;

use App\Enums\LetterStatus;
use App\Models\Letter;
use App\Services\Mail\LetterSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Renders and delivers one letter. The whole send is queued because dompdf and
 * SMTP together are far too slow for a web request.
 */
class SendLetter implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Letter $letter) {}

    public function handle(LetterSender $sender): void
    {
        $letter = $this->letter->fresh();

        // A scheduled send sits on the queue for hours, and the letter can be
        // cancelled in the meantime. The delayed job cannot be unqueued, so it
        // checks on arrival: anything no longer in Sending was cancelled,
        // released or already delivered, and must not go out.
        if ($letter === null || $letter->status !== LetterStatus::Sending) {
            return;
        }

        $letter->load('company');

        $sender->deliver($letter);
    }

    /**
     * Hand the letter back to the user rather than leaving it stuck in Sending,
     * with the reason on the record: by the time this runs there is no request
     * left to flash a message to.
     */
    public function failed(?Throwable $exception): void
    {
        $letter = $this->letter->fresh();

        if ($letter === null || $letter->sent_at !== null) {
            return;
        }

        $letter->forceFill([
            'status' => LetterStatus::Ready,
            'queued_at' => null,
            'scheduled_for' => null,
            'send_error' => $exception?->getMessage() ?? 'The letter could not be sent.',
        ])->save();
    }
}
