<?php

namespace App\Services\Mail;

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Folder;

/**
 * Appends an already-delivered message to the outreach account's IMAP Sent
 * folder, so mail this app sends shows up alongside mail sent by hand. Runs on
 * the queue: connecting to IMAP can take the full 30 second timeout, and a
 * successful send must never wait on it.
 */
class SentFolderAppender
{
    public function configured(): bool
    {
        return ! empty(config('services.outreach_imap.host'));
    }

    public function append(string $rawMessage): void
    {
        $config = config('services.outreach_imap');

        if (! is_array($config) || empty($config['host'])) {
            return;
        }

        $client = (new ClientManager)->make([
            'host' => $config['host'],
            'port' => $config['port'] ?? null,
            'encryption' => $config['encryption'] ?? null,
            'validate_cert' => true,
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
            'timeout' => 30,
        ]);
        $client->connect();

        $sentFolder = null;

        foreach ($client->getFolders(false) as $folder) {
            if (! $folder instanceof Folder) {
                continue;
            }

            if (str_contains(strtolower($folder->name), 'sent')) {
                $sentFolder = $folder;
                break;
            }
        }

        // appendMessage lives on the Folder, not the Client.
        $sentFolder?->appendMessage($rawMessage, ['Seen']);
    }
}
