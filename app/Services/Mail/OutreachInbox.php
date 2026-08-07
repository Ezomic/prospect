<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Log;
use Throwable;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

/**
 * IMAP-backed inbox for outreach. Reads unread INBOX messages from every
 * configured mailbox and marks Seen only the ones the handler acted on, so
 * unrelated mail is left alone.
 *
 * More than one mailbox because outreach predates this app: letters were sent
 * by hand from another address, and replies to those land somewhere the app
 * would otherwise never open.
 */
class OutreachInbox implements Inbox
{
    public function __construct(private readonly MailParser $parser) {}

    public function configured(): bool
    {
        return $this->mailboxes() !== [];
    }

    public function eachUnseen(callable $handler): void
    {
        foreach ($this->mailboxes() as $mailbox) {
            try {
                $this->readMailbox($mailbox, $handler);
            } catch (Throwable $e) {
                // One unreachable mailbox must not stop the others: the whole
                // point of several is that each is read independently.
                Log::warning('Could not poll outreach mailbox.', [
                    'host' => $mailbox['host'],
                    'username' => $mailbox['username'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Normalised so the rest of the class works with known types rather than
     * whatever shape the config happens to hold. An entry with no host is not
     * a mailbox, it is an unfilled slot in .env.
     *
     * @return list<array{host: string, port: int|null, encryption: string|null, username: string|null, password: string|null}>
     */
    private function mailboxes(): array
    {
        $mailboxes = config('services.outreach_mailboxes');

        if (! is_array($mailboxes)) {
            return [];
        }

        $configured = [];

        foreach ($mailboxes as $mailbox) {
            if (! is_array($mailbox)) {
                continue;
            }

            $host = $mailbox['host'] ?? null;

            if (! is_string($host) || $host === '') {
                continue;
            }

            $port = $mailbox['port'] ?? null;

            $configured[] = [
                'host' => $host,
                'port' => is_numeric($port) ? (int) $port : null,
                'encryption' => $this->stringOrNull($mailbox['encryption'] ?? null),
                'username' => $this->stringOrNull($mailbox['username'] ?? null),
                'password' => $this->stringOrNull($mailbox['password'] ?? null),
            ];
        }

        return $configured;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array{host: string, port: int|null, encryption: string|null, username: string|null, password: string|null}  $mailbox
     * @param  callable(IncomingMessage): bool  $handler
     */
    private function readMailbox(array $mailbox, callable $handler): void
    {
        $client = (new ClientManager)->make([
            'host' => $mailbox['host'],
            'port' => $mailbox['port'],
            'encryption' => $mailbox['encryption'],
            'validate_cert' => true,
            'username' => $mailbox['username'],
            'password' => $mailbox['password'],
            'timeout' => 30,
        ]);
        $client->connect();

        $inbox = $client->getFolderByPath('INBOX');

        if ($inbox === null) {
            return;
        }

        /** @var Message $message */
        foreach ($inbox->query()->unseen()->get() as $message) {
            if ($handler($this->normalize($message)) !== true) {
                continue;
            }

            try {
                $message->setFlag('Seen');
            } catch (Throwable) {
                // Best-effort: failing to flag must not stop the run.
            }
        }
    }

    private function normalize(Message $message): IncomingMessage
    {
        $sender = $message->getFrom()[0] ?? null;
        $from = is_object($sender) && isset($sender->mail) && is_string($sender->mail) ? $sender->mail : '';
        $body = $message->getTextBody() ?: (string) $message->getHTMLBody();

        return $this->parser->parse(
            $from,
            (string) $message->getSubject(),
            $body,
            (string) $message->getMessageId(),
            $this->autoReplyHeaders($message),
        );
    }

    /**
     * The headers that tell an automatic answer from a real one.
     *
     * @return array<string, string>
     */
    private function autoReplyHeaders(Message $message): array
    {
        $header = $message->getHeader();

        if ($header === null) {
            return [];
        }

        $headers = [];

        foreach (['auto-submitted', 'x-autoreply', 'x-autorespond', 'x-auto-reply', 'precedence'] as $name) {
            $value = trim((string) $header->get($name));

            if ($value !== '') {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
