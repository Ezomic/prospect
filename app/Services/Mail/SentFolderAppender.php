<?php

namespace App\Services\Mail;

use RuntimeException;
use Webklex\PHPIMAP\Client;
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

    /**
     * Throws rather than returning quietly when the folder cannot be found: a
     * silent no-op here is indistinguishable from a successful file, which is
     * how this went unproven for so long.
     */
    public function append(string $rawMessage): void
    {
        $sentFolder = null;

        foreach ($this->client()->getFolders(false) as $folder) {
            if (! $folder instanceof Folder) {
                continue;
            }

            if (str_contains(strtolower($folder->name), 'sent')) {
                $sentFolder = $folder;
                break;
            }
        }

        if ($sentFolder === null) {
            throw new RuntimeException(
                'No Sent folder was found on the outreach account. Folders seen: '.$this->folderNames().'.'
            );
        }

        // appendMessage lives on the Folder, not the Client.
        $sentFolder->appendMessage($rawMessage, ['Seen']);
    }

    private function client(): Client
    {
        $config = config('services.outreach_imap');

        if (! is_array($config) || empty($config['host'])) {
            throw new RuntimeException('No outreach IMAP host is configured.');
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

        return $client;
    }

    /**
     * Every folder the account exposes, so a failure says what it did find
     * rather than only what it wanted.
     *
     * @return list<string>
     */
    public function folders(): array
    {
        $names = [];

        foreach ($this->client()->getFolders(false) as $folder) {
            if ($folder instanceof Folder) {
                $names[] = $folder->name;
            }
        }

        return $names;
    }

    private function folderNames(): string
    {
        try {
            $names = $this->folders();
        } catch (\Throwable) {
            return 'none readable';
        }

        return $names === [] ? 'none' : implode(', ', $names);
    }
}
