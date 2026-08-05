<?php

namespace App\Jobs;

use App\Services\Mail\SentFolderAppender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Files a delivered message in the IMAP Sent folder. Separate from the send so
 * an unreachable IMAP host can never delay or undo a mail that already left.
 */
class AppendLetterToSentFolder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $rawMessagePath) {}

    public function handle(SentFolderAppender $appender): void
    {
        if (! Storage::disk('local')->exists($this->rawMessagePath)) {
            return;
        }

        if (! $appender->configured()) {
            Storage::disk('local')->delete($this->rawMessagePath);

            return;
        }

        $appender->append((string) Storage::disk('local')->get($this->rawMessagePath));

        Storage::disk('local')->delete($this->rawMessagePath);
    }

    public function failed(): void
    {
        Storage::disk('local')->delete($this->rawMessagePath);
    }
}
