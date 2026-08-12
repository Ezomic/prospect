<?php

namespace App\Services\Mail;

use Carbon\CarbonInterface;

/**
 * A normalized inbound message, decoupled from the IMAP layer so the outcome
 * logic can be reasoned about (and tested) without a mail server.
 */
class IncomingMessage
{
    /**
     * @param  list<string>  $failedRecipients  the addresses a bounce reports as undeliverable
     * @param  bool  $isAutoReply  an out-of-office or similar machine answer, not a real reply
     * @param  string  $body  plain text only: HTML from an unknown sender is an
     *                        XSS surface for no benefit
     */
    public function __construct(
        public readonly string $from,
        public readonly string $subject,
        public readonly bool $isBounce,
        public readonly array $failedRecipients = [],
        public readonly ?string $messageId = null,
        public readonly bool $isAutoReply = false,
        public readonly string $body = '',
        public readonly ?CarbonInterface $receivedAt = null,
    ) {}
}
