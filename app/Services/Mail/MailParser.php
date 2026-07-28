<?php

namespace App\Services\Mail;

use Illuminate\Support\Str;

/**
 * Turns the raw parts of an inbound message into a normalized IncomingMessage,
 * detecting bounces and the recipients they report as undeliverable. Kept free
 * of any IMAP dependency so the heuristics are testable in isolation.
 */
class MailParser
{
    private const BOUNCE_SENDERS = ['mailer-daemon', 'postmaster'];

    private const BOUNCE_SUBJECTS = [
        'undelivered',
        'undeliverable',
        'delivery status notification',
        'delivery failure',
        'mail delivery failed',
        'returned mail',
        'failure notice',
    ];

    public function parse(string $from, string $subject, string $body, ?string $messageId = null): IncomingMessage
    {
        $isBounce = $this->looksLikeBounce($from, $subject);

        return new IncomingMessage(
            from: strtolower(trim($from)),
            subject: $subject,
            isBounce: $isBounce,
            failedRecipients: $isBounce ? $this->failedRecipients($body) : [],
            messageId: $messageId,
        );
    }

    private function looksLikeBounce(string $from, string $subject): bool
    {
        return Str::contains(strtolower($from), self::BOUNCE_SENDERS)
            || Str::contains(strtolower($subject), self::BOUNCE_SUBJECTS);
    }

    /**
     * @return list<string>
     */
    private function failedRecipients(string $body): array
    {
        // Prefer the machine-readable DSN fields, which name the exact address.
        preg_match_all(
            '/(?:Final-Recipient|Original-Recipient):[^;\n]*;\s*(?:rfc822;)?\s*([^\s<>]+@[^\s<>]+)/i',
            $body,
            $matches,
        );

        $recipients = $matches[1];

        // Fall back to any address in the body when there is no DSN part.
        if ($recipients === []) {
            preg_match_all('/[\w.+-]+@[\w-]+\.[\w.-]+/', $body, $matches);
            $recipients = $matches[0];
        }

        return array_values(array_unique(array_map(
            fn (string $email) => strtolower(trim($email)),
            $recipients,
        )));
    }
}
