<?php

namespace App\Services\Mail;

/**
 * A normalized inbound message, decoupled from the IMAP layer so the outcome
 * logic can be reasoned about (and tested) without a mail server.
 */
class IncomingMessage
{
    /**
     * @param  list<string>  $failedRecipients  the addresses a bounce reports as undeliverable
     */
    public function __construct(
        public readonly string $from,
        public readonly string $subject,
        public readonly bool $isBounce,
        public readonly array $failedRecipients = [],
        public readonly ?string $messageId = null,
    ) {}
}
