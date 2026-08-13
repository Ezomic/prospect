<?php

use App\Enums\LetterStatus;
use App\Jobs\SendLetter;
use App\Models\Company;
use App\Models\Letter;
use App\Models\User;
use App\Services\Mail\LetterSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create());
});

function readyLetter(): Letter
{
    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);

    return Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);
}

it('schedules a send for a chosen moment', function () {
    Queue::fake();

    $letter = readyLetter();
    $when = now()->addDays(2)->setTime(9, 0);

    $this->post(route('letters.send', $letter), ['scheduled_for' => $when->toDateTimeString()])
        ->assertRedirect();

    Queue::assertPushed(SendLetter::class, fn (SendLetter $job) => $job->delay !== null);

    expect($letter->fresh())
        ->status->toBe(LetterStatus::Sending)
        ->and($letter->fresh()->scheduled_for->toDateTimeString())->toBe($when->toDateTimeString());
});

it('sends immediately when no moment is given', function () {
    Queue::fake();

    $letter = readyLetter();

    $this->post(route('letters.send', $letter))->assertRedirect();

    Queue::assertPushed(SendLetter::class, fn (SendLetter $job) => $job->delay === null);

    expect($letter->fresh()->scheduled_for)->toBeNull();
});

it('refuses a moment in the past', function () {
    Queue::fake();

    $letter = readyLetter();

    $this->post(route('letters.send', $letter), ['scheduled_for' => now()->subHour()->toDateTimeString()])
        ->assertSessionHasErrors('scheduled_for');

    Queue::assertNothingPushed();
    expect($letter->fresh()->status)->toBe(LetterStatus::Ready);
});

it('cancels a scheduled send that has not run', function () {
    Queue::fake();

    $letter = readyLetter();

    $this->post(route('letters.send', $letter), ['scheduled_for' => now()->addDay()->toDateTimeString()]);

    $this->post(route('letters.cancel', $letter))->assertRedirect();

    expect($letter->fresh())
        ->status->toBe(LetterStatus::Ready)
        ->scheduled_for->toBeNull()
        ->queued_at->toBeNull();
});

it('does not deliver a cancelled letter when the delayed job finally runs', function () {
    Mail::fake();

    $letter = readyLetter();
    $letter->forceFill([
        'status' => LetterStatus::Ready,
        'scheduled_for' => null,
        'queued_at' => null,
    ])->save();

    // The job cannot be pulled off the queue, so it must check on arrival.
    (new SendLetter($letter))->handle(app(LetterSender::class));

    Mail::assertNothingSent();
    expect($letter->fresh()->sent_at)->toBeNull();
});

it('will not cancel a send that was not scheduled', function () {
    Queue::fake();

    $letter = readyLetter();
    $this->post(route('letters.send', $letter));

    $this->post(route('letters.cancel', $letter))->assertRedirect();

    expect($letter->fresh()->status)->toBe(LetterStatus::Sending);
});

it('does not treat a scheduled letter as stuck while it waits', function () {
    Queue::fake();
    config(['outreach.stuck_after_minutes' => 5]);

    $letter = readyLetter();

    // Queued now, due tomorrow: hours in Sending is exactly what a schedule is.
    $this->post(route('letters.send', $letter), ['scheduled_for' => now()->addDay()->toDateTimeString()]);

    $this->get(route('letters.edit', $letter))
        ->assertInertia(fn (Assert $page) => $page
            ->where('releasable', false)
            ->where('cancellable', true)
        );
});

it('treats a scheduled letter as stuck once its moment has passed', function () {
    config(['outreach.stuck_after_minutes' => 5]);

    $letter = readyLetter();
    $letter->forceFill([
        'status' => LetterStatus::Sending,
        'queued_at' => now()->subDays(2),
        'scheduled_for' => now()->subHour(),
    ])->save();

    $this->get(route('letters.edit', $letter))
        ->assertInertia(fn (Assert $page) => $page
            ->where('releasable', true)
            ->where('cancellable', false)
        );
});
