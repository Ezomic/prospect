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

    /**
     * Each pattern names an address the reporting server itself flagged as
     * undeliverable: the DSN fields, the Gmail-style header, and the Postfix
     * "<address>: host ... said:" line.
     */
    private const FAILED_RECIPIENT_PATTERNS = [
        '/(?:Final-Recipient|Original-Recipient):[^;\n]*;\s*(?:rfc822;)?\s*([^\s<>]+@[^\s<>]+)/i',
        '/X-Failed-Recipients:\s*([^\s<>]+@[^\s<>,]+)/i',
        '/^\s*<([\w.+-]+@[\w-]+\.[\w.-]+)>:\s/im',
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
     * Only addresses a bounce explicitly names as undeliverable count. A bounce
     * quotes the original message, so any address in the body is emphatically
     * not a failed recipient: matching those marked the wrong company bounced.
     *
     * @return list<string>
     */
    private function failedRecipients(string $body): array
    {
        $recipients = [];

        foreach (self::FAILED_RECIPIENT_PATTERNS as $pattern) {
            preg_match_all($pattern, $body, $matches);

            $recipients = [...$recipients, ...$matches[1]];
        }

        $recipients = [...$recipients, ...$this->addressesInFailureBlock($body)];

        return array_values(array_unique(array_map(
            fn (string $email) => strtolower(trim($email, " \t\n\r<>.,;")),
            $recipients,
        )));
    }

    /**
     * Exim and friends list the failed addresses on the lines following a
     * header phrase rather than naming them inline.
     *
     * @return list<string>
     */
    private function addressesInFailureBlock(string $body): array
    {
        if (preg_match('/following\s+address(?:\(es\)|es)?\s+failed:(.*)$/is', $body, $block) !== 1) {
            return [];
        }

        // Stop at the first blank line so the quoted original is never read.
        $lines = preg_split('/\R/', trim($block[1])) ?: [];
        $addresses = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                break;
            }

            if (preg_match('/([\w.+-]+@[\w-]+\.[\w.-]+)/', $line, $match) === 1) {
                $addresses[] = $match[1];
            }
        }

        return $addresses;
    }
}
