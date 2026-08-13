<?php

use App\Actions\Outreach\ProcessIncomingMail;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Letter;
use App\Models\User;
use App\Services\Mail\IncomingMessage;
use App\Services\Mail\LetterSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
    $this->actingAs(User::factory()->create());
});

function deliverTo(Company $company): Letter
{
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);
    $letter->load('company');

    app(LetterSender::class)->deliver($letter);

    return $letter;
}

it('schedules a follow-up when a letter is sent', function () {
    config(['outreach.follow_up_after_days' => 14]);

    $company = Company::factory()->create([
        'email' => 'hr@acme.example',
        'status' => 'new',
        'follow_up_at' => null,
    ]);

    deliverTo($company);

    expect($company->fresh()->follow_up_at->toDateString())
        ->toBe(today()->addDays(14)->toDateString());
});

it('never overwrites a reminder the user set', function () {
    config(['outreach.follow_up_after_days' => 14]);

    $chosen = today()->addDays(3);
    $company = Company::factory()->create([
        'email' => 'hr@acme.example',
        'status' => 'new',
        'follow_up_at' => $chosen,
    ]);

    deliverTo($company);

    expect($company->fresh()->follow_up_at->toDateString())->toBe($chosen->toDateString());
});

it('leaves follow-ups manual when the setting is zero', function () {
    config(['outreach.follow_up_after_days' => 0]);

    $company = Company::factory()->create([
        'email' => 'hr@acme.example',
        'status' => 'new',
        'follow_up_at' => null,
    ]);

    deliverTo($company);

    expect($company->fresh()->follow_up_at)->toBeNull();
});

it('does not schedule a chase for a company that already replied', function () {
    config(['outreach.follow_up_after_days' => 14]);

    $company = Company::factory()->create([
        'email' => 'hr@acme.example',
        'status' => 'replied',
        'replied_at' => now()->subDay(),
        'follow_up_at' => null,
    ]);

    deliverTo($company);

    expect($company->fresh()->follow_up_at)->toBeNull();
});

it('clears the reminder when a company replies', function () {
    $company = Company::factory()->create([
        'email' => 'hr@acme.example',
        'status' => 'sent',
        'follow_up_at' => today()->addWeek(),
    ]);

    (new ProcessIncomingMail)->handle(new IncomingMessage(
        from: 'hr@acme.example',
        subject: 'Re: Open aanbod',
        isBounce: false,
    ));

    expect($company->fresh())
        ->status->toBe(CompanyStatus::Replied)
        ->follow_up_at->toBeNull();
});

it('clears the reminder when a letter bounces', function () {
    $company = Company::factory()->create([
        'email' => 'hr@acme.example',
        'status' => 'sent',
        'follow_up_at' => today()->addWeek(),
    ]);

    (new ProcessIncomingMail)->handle(new IncomingMessage(
        from: 'mailer-daemon@x',
        subject: 'Undelivered',
        isBounce: true,
        failedRecipients: ['hr@acme.example'],
    ));

    expect($company->fresh()->follow_up_at)->toBeNull();
});
