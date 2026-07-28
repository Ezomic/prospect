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

        $client = (new ClientManager)->make([
            'host' => $config['host'],
            'port' => $config['port'],
            'encryption' => $config['encryption'],
            'validate_cert' => true,
            'username' => $config['username'],
            'password' => $config['password'],
            'timeout' => 30,
        ]);
        $client->connect();

        $messages = $client->getFolderByPath('INBOX')->query()->unseen()->get();

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
        $from = $message->getFrom()[0]->mail ?? '';
        $body = $message->getTextBody() ?: (string) $message->getHTMLBody();

        return $this->parser->parse(
            (string) $from,
            (string) $message->getSubject(),
            $body,
            (string) $message->getMessageId(),
        );
    }
}
