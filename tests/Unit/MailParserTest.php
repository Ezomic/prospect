<?php

use App\Services\Mail\MailParser;

beforeEach(function () {
    $this->parser = new MailParser;
});

it('treats a normal message as a reply', function () {
    $message = $this->parser->parse('Jane Doe <jane@acme.example>', 'Re: Open sollicitatie', 'Bedankt voor je bericht.');

    expect($message->isBounce)->toBeFalse()
        ->and($message->from)->toBe('jane doe <jane@acme.example>')
        ->and($message->failedRecipients)->toBe([]);
});

it('detects a bounce from mailer-daemon and parses the failed recipient', function () {
    $body = "Your message could not be delivered.\n\nFinal-Recipient: rfc822; hr@acme.example\nAction: failed\nStatus: 5.1.1\n";

    $message = $this->parser->parse('MAILER-DAEMON@mail.example', 'Undelivered Mail Returned to Sender', $body);

    expect($message->isBounce)->toBeTrue()
        ->and($message->failedRecipients)->toContain('hr@acme.example');
});

it('detects a bounce by subject when the sender is not a daemon', function () {
    $message = $this->parser->parse('postmaster@acme.example', 'Delivery Status Notification (Failure)', 'hr@acme.example failed');

    expect($message->isBounce)->toBeTrue()
        ->and($message->failedRecipients)->toContain('hr@acme.example');
});

it('lowercases and de-duplicates parsed recipients', function () {
    $body = "Final-Recipient: rfc822; HR@Acme.Example\nOriginal-Recipient: rfc822; hr@acme.example";

    $message = $this->parser->parse('mailer-daemon@x', 'failure notice', $body);

    expect($message->failedRecipients)->toBe(['hr@acme.example']);
});
