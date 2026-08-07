<?php

namespace App\Console\Commands;

use App\Services\Mail\SentFolderAppender;
use Illuminate\Console\Command;
use Throwable;

/**
 * Proves the Sent-folder path against the real mailbox without sending
 * anything. The append runs on the queue and used to fail quietly, so there
 * was no way to tell a working setup from a broken one short of reading the
 * mailbox by hand.
 */
class CheckSentFolder extends Command
{
    protected $signature = 'outreach:check-sent-folder';

    protected $description = 'Check that the outreach account exposes a Sent folder the app can file into';

    public function handle(SentFolderAppender $appender): int
    {
        if (! $appender->configured()) {
            $this->warn('Outreach IMAP is not configured, so sent letters are never filed.');

            return self::FAILURE;
        }

        try {
            $folders = $appender->folders();
        } catch (Throwable $e) {
            $this->error('Could not read the mailbox: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line('Folders: '.($folders === [] ? 'none' : implode(', ', $folders)));

        $sent = array_values(array_filter(
            $folders,
            fn (string $folder) => str_contains(strtolower($folder), 'sent'),
        ));

        if ($sent === []) {
            $this->error('No folder matching "sent" was found, so every append will fail.');

            return self::FAILURE;
        }

        $this->info('Sent letters will be filed in: '.$sent[0]);

        return self::SUCCESS;
    }
}
