<?php

use App\Actions\Outreach\ProcessIncomingMail;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Services\Mail\IncomingMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new ProcessIncomingMail;
});

it('marks a sent company replied when it answers', function () {
    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent', 'replied_at' => null]);

    $this->action->handle(new IncomingMessage(
        from: 'hr@acme.example',
        subject: 'Re: Open sollicitatie',
        isBounce: false,
    ));

    expect($company->fresh())
        ->status->toBe(CompanyStatus::Replied)
        ->and($company->fresh()->replied_at)->not->toBeNull();
});

it('marks a sent company bounced from a failed recipient', function () {
    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent', 'bounced_at' => null]);

    $this->action->handle(new IncomingMessage(
        from: 'mailer-daemon@mail.example',
        subject: 'Undelivered',
        isBounce: true,
        failedRecipients: ['hr@acme.example'],
    ));

    expect($company->fresh())
        ->status->toBe(CompanyStatus::Bounced)
        ->and($company->fresh()->bounced_at)->not->toBeNull();
});

it('ignores replies from unknown senders', function () {
    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    $this->action->handle(new IncomingMessage('stranger@nowhere.example', 'Hello', false));

    expect($company->fresh()->status)->toBe(CompanyStatus::Sent);
});

it('does not transition a company that is not in sent', function () {
    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);

    $this->action->handle(new IncomingMessage('hr@acme.example', 'Re: hi', false));

    expect($company->fresh()->status)->toBe(CompanyStatus::New);
});

it('is a no-op when reprocessing an already-replied company', function () {
    $repliedAt = now()->subDays(3);
    $company = Company::factory()->create([
        'email' => 'hr@acme.example',
        'status' => 'replied',
        'replied_at' => $repliedAt,
    ]);

    $this->action->handle(new IncomingMessage('hr@acme.example', 'Re: hi again', false));

    expect($company->fresh()->replied_at->toDateTimeString())->toBe($repliedAt->toDateTimeString());
});

it('does not mark a company replied on an out-of-office notice', function () {
    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent', 'replied_at' => null]);

    $this->action->handle(new IncomingMessage(
        from: 'hr@acme.example',
        subject: 'Automatisch antwoord: Open sollicitatie',
        isBounce: false,
        isAutoReply: true,
    ));

    expect($company->fresh())
        ->status->toBe(CompanyStatus::Sent)
        ->and($company->fresh()->replied_at)->toBeNull();
});

it('still marks a company bounced when the delivery report is auto-submitted', function () {
    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    $this->action->handle(new IncomingMessage(
        from: 'mailer-daemon@mail.example',
        subject: 'Undelivered Mail Returned to Sender',
        isBounce: true,
        failedRecipients: ['hr@acme.example'],
        isAutoReply: true,
    ));

    expect($company->fresh()->status)->toBe(CompanyStatus::Bounced);
});

it('reports whether it acted on the message', function () {
    Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    // A reply from a company we emailed.
    expect($this->action->handle(new IncomingMessage('hr@acme.example', 'Re: hoi', false)))->toBeTrue();

    // A stranger writing to the mailbox.
    expect($this->action->handle(new IncomingMessage('stranger@nowhere.example', 'Offerte?', false)))->toBeFalse();
});

it('does not treat an out-of-office as acted on, so it stays unread', function () {
    Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    $acted = $this->action->handle(new IncomingMessage(
        from: 'hr@acme.example',
        subject: 'Automatisch antwoord',
        isBounce: false,
        isAutoReply: true,
    ));

    expect($acted)->toBeFalse();
});

it('reports a bounce as acted on only when it matched a company', function () {
    Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    expect($this->action->handle(new IncomingMessage(
        from: 'mailer-daemon@x', subject: 'Undelivered', isBounce: true,
        failedRecipients: ['hr@acme.example'],
    )))->toBeTrue();

    expect($this->action->handle(new IncomingMessage(
        from: 'mailer-daemon@x', subject: 'Undelivered', isBounce: true,
        failedRecipients: ['someone@unknown.example'],
    )))->toBeFalse();
});
