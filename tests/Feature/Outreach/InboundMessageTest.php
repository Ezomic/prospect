<?php

use App\Actions\Outreach\ProcessIncomingMail;
use App\Enums\InboundMessageKind;
use App\Models\Company;
use App\Models\InboundMessage;
use App\Models\User;
use App\Services\Mail\IncomingMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new ProcessIncomingMail;
});

it('keeps the reply that marked a company replied', function () {
    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    $this->action->handle(new IncomingMessage(
        from: 'hr@acme.example',
        subject: 'Re: Open aanbod',
        isBounce: false,
        messageId: '<abc@mail.example>',
        body: "Dank voor uw bericht.\n\nWe nemen contact op.",
        receivedAt: now()->subHour(),
    ));

    $message = $company->inboundMessages()->sole();

    expect($message->kind)->toBe(InboundMessageKind::Reply)
        ->and($message->from)->toBe('hr@acme.example')
        ->and($message->subject)->toBe('Re: Open aanbod')
        ->and($message->body)->toContain('We nemen contact op.')
        ->and($message->received_at->toDateTimeString())->toBe(now()->subHour()->toDateTimeString());
});

it('keeps the bounce that marked a company bounced', function () {
    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    $this->action->handle(new IncomingMessage(
        from: 'mailer-daemon@mail.example',
        subject: 'Undelivered Mail Returned to Sender',
        isBounce: true,
        failedRecipients: ['hr@acme.example'],
        messageId: '<bounce@mail.example>',
        body: '550 5.1.1 unknown recipient',
    ));

    expect($company->inboundMessages()->sole()->kind)->toBe(InboundMessageKind::Bounce);
});

it('does not store the same message twice when it is polled again', function () {
    Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    $message = new IncomingMessage(
        from: 'hr@acme.example',
        subject: 'Re: Open aanbod',
        isBounce: false,
        messageId: '<same@mail.example>',
        body: 'Eerste keer.',
    );

    $this->action->handle($message);

    // The company is no longer Sent, so a second pass transitions nothing, but
    // the storage would still duplicate if it were not deduplicated.
    Company::query()->update(['status' => 'sent']);
    $this->action->handle($message);

    expect(InboundMessage::count())->toBe(1);
});

it('stores a message with no id rather than discarding it', function () {
    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    $this->action->handle(new IncomingMessage(
        from: 'hr@acme.example',
        subject: 'Re: Open aanbod',
        isBounce: false,
        messageId: null,
        body: 'Geen message id.',
    ));

    expect($company->inboundMessages()->count())->toBe(1);
});

it('stores nothing when no company matched', function () {
    $this->action->handle(new IncomingMessage(
        from: 'stranger@nowhere.example',
        subject: 'Offerte?',
        isBounce: false,
        messageId: '<x@mail.example>',
        body: 'Hallo.',
    ));

    expect(InboundMessage::count())->toBe(0);
});

it('stores nothing for an out-of-office, which changes nothing', function () {
    Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    $this->action->handle(new IncomingMessage(
        from: 'hr@acme.example',
        subject: 'Automatisch antwoord',
        isBounce: false,
        messageId: '<ooo@mail.example>',
        isAutoReply: true,
        body: 'Ik ben afwezig.',
    ));

    expect(InboundMessage::count())->toBe(0);
});

it('shows stored messages on the company page, newest first', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['created_at' => now()->subYear()]);
    InboundMessage::factory()->create([
        'company_id' => $company->id,
        'subject' => 'Ouder bericht',
        'body' => 'Oud.',
        'received_at' => now()->subWeek(),
    ]);
    InboundMessage::factory()->create([
        'company_id' => $company->id,
        'subject' => 'Nieuwer bericht',
        'body' => 'Nieuw.',
        'received_at' => now()->subDay(),
    ]);

    // Surfaced through the timeline since PROS-45; the guarantee is unchanged.
    $this->get(route('companies.show', $company))
        ->assertInertia(function (Assert $page) {
            $replies = array_values(array_filter(
                $page->toArray()['props']['timeline'],
                fn ($entry) => $entry['kind'] === 'reply',
            ));

            expect($replies)->toHaveCount(2)
                ->and($replies[0]['detail'])->toContain('Nieuwer bericht')
                ->and($replies[1]['detail'])->toContain('Ouder bericht');
        });
});

it('goes when the company does', function () {
    $company = Company::factory()->create();
    InboundMessage::factory()->create(['company_id' => $company->id]);

    $company->delete();

    expect(InboundMessage::count())->toBe(0);
});

it('stores one bounce against each company it names', function () {
    $acme = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);
    $globex = Company::factory()->create(['email' => 'jobs@globex.example', 'status' => 'sent']);

    // One message, two tracked recipients. Deduplication is per company, so
    // this is two rows sharing a message id rather than a unique collision.
    $this->action->handle(new IncomingMessage(
        from: 'mailer-daemon@mail.example',
        subject: 'Undelivered Mail Returned to Sender',
        isBounce: true,
        failedRecipients: ['hr@acme.example', 'jobs@globex.example'],
        messageId: '<shared@mail.example>',
        body: '550 unknown',
    ));

    expect($acme->inboundMessages()->count())->toBe(1)
        ->and($globex->inboundMessages()->count())->toBe(1)
        ->and(InboundMessage::count())->toBe(2);
});
