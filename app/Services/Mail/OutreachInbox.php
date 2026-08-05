<?php

namespace App\Services\Mail;

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

/**
 * IMAP-backed inbox for the outreach account. Connects with the
 * services.outreach_imap credentials, reads unseen INBOX messages and marks
 * each Seen once handled so it is processed only once.
 */
class OutreachInbox implements Inbox
{
    public function __construct(private readonly MailParser $parser) {}

    public function configured(): bool
    {
        return ! empty(config('services.outreach_imap.host'));
    }

    public function eachUnseen(callable $handler): void
    {
        if (! $this->configured()) {
            return;
        }

        $config = config('services.outreach_imap');

        if (! is_array($config)) {
            return;
        }

        $client = (new ClientManager)->make([
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,
            'encryption' => $config['encryption'] ?? null,
            'validate_cert' => true,
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
            'timeout' => 30,
        ]);
        $client->connect();

        $inbox = $client->getFolderByPath('INBOX');

        if ($inbox === null) {
            return;
        }

        $messages = $inbox->query()->unseen()->get();

        /** @var Message $message */
        foreach ($messages as $message) {
            $handler($this->normalize($message));

            try {
                $message->setFlag('Seen');
            } catch (\Throwable) {
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
