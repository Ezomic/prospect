<?php

use App\Services\Mail\Inbox;
use App\Services\Mail\OutreachInbox;

it('reads no mailboxes when none are configured', function () {
    config(['services.outreach_mailboxes' => []]);

    expect(app(OutreachInbox::class)->configured())->toBeFalse();
});

it('is configured once at least one mailbox has a host', function () {
    config(['services.outreach_mailboxes' => [
        ['host' => 'mail.example', 'username' => 'werk@example', 'password' => 'x'],
    ]]);

    expect(app(OutreachInbox::class)->configured())->toBeTrue();
});

it('ignores entries without a host', function () {
    config(['services.outreach_mailboxes' => [
        ['host' => null, 'username' => 'unset@example'],
        ['host' => '', 'username' => 'empty@example'],
    ]]);

    expect(app(OutreachInbox::class)->configured())->toBeFalse();
});

it('keeps reading the remaining mailboxes when one cannot be reached', function () {
    // Both hosts are unreachable, so each attempt throws inside eachUnseen.
    // The call itself must still return rather than surfacing the first error.
    config(['services.outreach_mailboxes' => [
        ['host' => 'unreachable-1.invalid', 'port' => 993, 'encryption' => 'ssl', 'username' => 'a', 'password' => 'b'],
        ['host' => 'unreachable-2.invalid', 'port' => 993, 'encryption' => 'ssl', 'username' => 'c', 'password' => 'd'],
    ]]);

    $handled = 0;

    app(Inbox::class)->eachUnseen(function () use (&$handled) {
        $handled++;

        return true;
    });

    expect($handled)->toBe(0);
})->skipOnWindows();
