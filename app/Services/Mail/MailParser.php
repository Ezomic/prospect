<?php

namespace App\Services\Mail;

use Illuminate\Support\Str;

/**
 * Turns the raw parts of an inbound message into a normalized IncomingMessage,
 * detecting bounces, the recipients they report as undeliverable, and machine
 * answers such as out-of-office notices. Kept free of any IMAP dependency so
 * the heuristics are testable in isolation.
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

    private const AUTO_REPLY_HEADERS = ['x-autoreply', 'x-autorespond', 'x-auto-reply'];

    private const AUTO_REPLY_SUBJECTS = [
        'automatisch antwoord',
        'automatische reactie',
        'afwezig',
        'afwezigheid',
        'out of office',
        'out of the office',
        'automatic reply',
        'autoreply',
        'auto-reply',
        'autoresponse',
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

    /**
     * @param  array<string, string>  $headers  header values keyed by lowercased name
     */
    public function parse(
        string $from,
        string $subject,
        string $body,
        ?string $messageId = null,
        array $headers = [],
    ): IncomingMessage {
        $isBounce = $this->looksLikeBounce($from, $subject);

        return new IncomingMessage(
            from: strtolower(trim($from)),
            subject: $subject,
            isBounce: $isBounce,
            failedRecipients: $isBounce ? $this->failedRecipients($body) : [],
            messageId: $messageId,
            isAutoReply: $this->looksLikeAutoReply($subject, $headers),
        );
    }

    private function looksLikeBounce(string $from, string $subject): bool
    {
        return Str::contains(strtolower($from), self::BOUNCE_SENDERS)
            || Str::contains(strtolower($subject), self::BOUNCE_SUBJECTS);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function looksLikeAutoReply(string $subject, array $headers): bool
    {
        $headers = array_change_key_case($headers);

        // RFC 3834: anything other than "no" means the message was generated
        // automatically. Delivery reports carry it too, so callers must test
        // for a bounce first.
        $autoSubmitted = strtolower(trim($headers['auto-submitted'] ?? 'no'));

        if ($autoSubmitted !== '' && $autoSubmitted !== 'no') {
            return true;
        }

        foreach (self::AUTO_REPLY_HEADERS as $header) {
            if (trim($headers[$header] ?? '') !== '') {
                return true;
            }
        }

        if (strtolower(trim($headers['precedence'] ?? '')) === 'auto_reply') {
            return true;
        }

        return Str::contains(strtolower($subject), self::AUTO_REPLY_SUBJECTS);
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
