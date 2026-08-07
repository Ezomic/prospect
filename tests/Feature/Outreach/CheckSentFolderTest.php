<?php

it('reports when imap is not configured rather than passing quietly', function () {
    config(['services.outreach_imap' => ['host' => null]]);

    $this->artisan('outreach:check-sent-folder')
        ->expectsOutputToContain('not configured')
        ->assertFailed();
});

it('fails when the mailbox cannot be read', function () {
    config(['services.outreach_imap' => [
        'host' => 'unreachable.invalid',
        'port' => 993,
        'encryption' => 'ssl',
        'username' => 'a',
        'password' => 'b',
    ]]);

    $this->artisan('outreach:check-sent-folder')
        ->expectsOutputToContain('Could not read the mailbox')
        ->assertFailed();
});
