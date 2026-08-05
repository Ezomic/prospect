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
    $body = "<hr@acme.example>: host mx.acme.example[10.0.0.1] said: 550 5.1.1 unknown\n";

    $message = $this->parser->parse('postmaster@acme.example', 'Delivery Status Notification (Failure)', $body);

    expect($message->isBounce)->toBeTrue()
        ->and($message->failedRecipients)->toContain('hr@acme.example');
});

it('lowercases and de-duplicates parsed recipients', function () {
    $body = "Final-Recipient: rfc822; HR@Acme.Example\nOriginal-Recipient: rfc822; hr@acme.example";

    $message = $this->parser->parse('mailer-daemon@x', 'failure notice', $body);

    expect($message->failedRecipients)->toBe(['hr@acme.example']);
});

it('ignores addresses that a bounce merely quotes', function () {
    $body = <<<'BODY'
    Your message could not be delivered.

    Final-Recipient: rfc822; hr@acme.example
    Action: failed
    Status: 5.1.1

    ----- Original message -----
    From: info@thijssensoftware.nl
    To: hr@acme.example
    Cc: someone-else@othercompany.example
    Subject: Open sollicitatie

    Geachte heer, mevrouw, ... reageer gerust op contact@thirdparty.example
    BODY;

    $message = $this->parser->parse('MAILER-DAEMON@mail.example', 'Undelivered Mail Returned to Sender', $body);

    expect($message->failedRecipients)->toBe(['hr@acme.example']);
});

it('returns no failed recipients when a bounce names none', function () {
    $body = "Delivery to the following recipient has been delayed.\n\nOriginal message from info@thijssensoftware.nl\n";

    $message = $this->parser->parse('mailer-daemon@mail.example', 'Delivery Status Notification', $body);

    expect($message->isBounce)->toBeTrue()
        ->and($message->failedRecipients)->toBe([]);
});

it('parses the gmail style failed recipients header', function () {
    $body = "X-Failed-Recipients: hr@acme.example\n\nThe response was: 550 no such user\n";

    $message = $this->parser->parse('mailer-daemon@googlemail.com', 'Delivery Status Notification (Failure)', $body);

    expect($message->failedRecipients)->toBe(['hr@acme.example']);
});

it('parses an exim style failed address block without reading the quoted original', function () {
    $body = <<<'BODY'
    This message was created automatically by mail delivery software.

    The following address(es) failed:

      hr@acme.example
      sales@acme.example

    ------ This is a copy of the message. ------

    To: bystander@othercompany.example
    BODY;

    $message = $this->parser->parse('Mail Delivery System <MAILER-DAEMON@mx.example>', 'Mail delivery failed', $body);

    expect($message->failedRecipients)->toBe(['hr@acme.example', 'sales@acme.example']);
});

it('flags an out-of-office by the auto-submitted header', function () {
    $message = $this->parser->parse(
        'jane@acme.example',
        'Re: Open sollicitatie',
        'Ik ben afwezig tot 1 september.',
        null,
        ['Auto-Submitted' => 'auto-replied'],
    );

    expect($message->isAutoReply)->toBeTrue()
        ->and($message->isBounce)->toBeFalse();
});

it('does not flag an auto-submitted header of no', function () {
    $message = $this->parser->parse(
        'jane@acme.example',
        'Re: Open sollicitatie',
        'Bedankt voor je bericht.',
        null,
        ['auto-submitted' => 'no'],
    );

    expect($message->isAutoReply)->toBeFalse();
});

it('flags an out-of-office by header', function (string $header) {
    $message = $this->parser->parse('jane@acme.example', 'Re: hoi', 'body', null, [$header => 'yes']);

    expect($message->isAutoReply)->toBeTrue();
})->with(['x-autoreply', 'x-autorespond', 'x-auto-reply']);

it('flags an auto_reply precedence', function () {
    $message = $this->parser->parse('jane@acme.example', 'Re: hoi', 'body', null, ['precedence' => 'auto_reply']);

    expect($message->isAutoReply)->toBeTrue();
});

it('flags an out-of-office by subject', function (string $subject) {
    $message = $this->parser->parse('jane@acme.example', $subject, 'body');

    expect($message->isAutoReply)->toBeTrue();
})->with([
    'Automatisch antwoord: Open sollicitatie',
    'Afwezig tot 1 september',
    'Out of Office: Re: Open sollicitatie',
    'Automatic reply: Open sollicitatie',
    'AutoReply from Acme',
]);

it('treats a genuine reply as no auto-reply', function () {
    $message = $this->parser->parse('jane@acme.example', 'Re: Open sollicitatie', 'Leuk, laten we kennismaken.');

    expect($message->isAutoReply)->toBeFalse();
});
