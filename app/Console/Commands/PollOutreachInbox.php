<?php

namespace App\Console\Commands;

use App\Actions\Outreach\ProcessIncomingMail;
use App\Services\Mail\Inbox;
use Illuminate\Console\Command;

class PollOutreachInbox extends Command
{
    protected $signature = 'outreach:poll';

    protected $description = 'Poll the outreach mailbox and mark companies replied or bounced';

    public function handle(Inbox $inbox, ProcessIncomingMail $processor): int
    {
        if (! $inbox->configured()) {
            $this->warn('Outreach IMAP is not configured; skipping.');

            return self::SUCCESS;
        }

        $inbox->eachUnseen(fn ($message) => $processor->handle($message));

        $this->info('Outreach mailbox polled.');

        return self::SUCCESS;
    }
}
