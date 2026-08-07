<?php

namespace App\Jobs;

use App\Services\Mail\SentFolderAppender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

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

        Log::info('Filed a sent letter in the IMAP Sent folder.', [
            'message' => $this->rawMessagePath,
        ]);

        Storage::disk('local')->delete($this->rawMessagePath);
    }

    /**
     * The stashed message is kept rather than deleted. It is the only copy of
     * a mail that really was sent, and throwing it away on the last retry
     * would leave no way to file it by hand afterwards.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Could not file a sent letter in the IMAP Sent folder.', [
            'message' => $this->rawMessagePath,
            'error' => $exception?->getMessage(),
        ]);
    }
}
