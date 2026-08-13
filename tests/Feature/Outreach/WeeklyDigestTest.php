<?php

use App\Actions\Outreach\BuildWeeklyDigest;
use App\Enums\InboundMessageKind;
use App\Mail\WeeklyDigestMail;
use App\Models\Company;
use App\Models\InboundMessage;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('counts the week and says plainly when nothing happened', function () {
    Company::factory()->count(2)->create(['email' => null]);

    $digest = app(BuildWeeklyDigest::class)->handle();

    expect($digest)
        ->sent->toBe(0)
        ->replies->toBe(0)
        ->bounces->toBe(0)
        ->missingEmail->toBe(2)
        // The weeks with no activity are exactly the ones worth reporting.
        ->quiet->toBeTrue();
});

it('counts what happened this week and ignores older activity', function () {
    Letter::factory()->create(['sent_at' => now()->subDays(2)]);
    Letter::factory()->create(['sent_at' => now()->subMonth()]);

    $company = Company::factory()->create();
    InboundMessage::factory()->create([
        'company_id' => $company->id,
        'kind' => InboundMessageKind::Reply,
        'received_at' => now()->subDay(),
    ]);
    InboundMessage::factory()->create([
        'company_id' => $company->id,
        'kind' => InboundMessageKind::Bounce,
        'received_at' => now()->subDays(3),
    ]);
    InboundMessage::factory()->create([
        'company_id' => $company->id,
        'kind' => InboundMessageKind::Reply,
        'received_at' => now()->subMonths(2),
    ]);

    $digest = app(BuildWeeklyDigest::class)->handle();

    expect($digest)
        ->sent->toBe(1)
        ->replies->toBe(1)
        ->bounces->toBe(1)
        ->quiet->toBeFalse();
});

it('separates overdue follow-ups from the ones coming up', function () {
    Company::factory()->create(['status' => 'sent', 'follow_up_at' => today()->subWeek()]);
    Company::factory()->create(['status' => 'sent', 'follow_up_at' => today()->addDays(2)]);
    // Closed and do-not-contact companies are not chased, so they do not count.
    Company::factory()->create(['status' => 'closed', 'follow_up_at' => today()->subWeek()]);
    Company::factory()->create([
        'status' => 'sent',
        'follow_up_at' => today()->subWeek(),
        'do_not_contact' => true,
    ]);

    $digest = app(BuildWeeklyDigest::class)->handle();

    expect($digest)->overdue->toBe(1)->dueSoon->toBe(1);
});

it('emails the digest to the user', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->artisan('outreach:digest')->assertSuccessful();

    Mail::assertSent(WeeklyDigestMail::class, fn (WeeklyDigestMail $mail) => $mail->hasTo($user->email));
});

it('says so in the subject when the week was quiet', function () {
    Mail::fake();

    User::factory()->create();

    $this->artisan('outreach:digest');

    Mail::assertSent(WeeklyDigestMail::class, function (WeeklyDigestMail $mail) {
        return str_contains($mail->envelope()->subject, 'niets verstuurd');
    });
});

it('does nothing when there is no user to send to', function () {
    Mail::fake();

    $this->artisan('outreach:digest')->assertSuccessful();

    Mail::assertNothingSent();
});
