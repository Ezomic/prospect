<?php

use App\Enums\InboundMessageKind;
use App\Enums\InteractionKind;
use App\Models\Company;
use App\Models\InboundMessage;
use App\Models\Interaction;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists everything that happened, newest first', function () {
    $company = Company::factory()->create(['created_at' => now()->subMonths(2)]);

    Letter::factory()->create([
        'company_id' => $company->id,
        'generated_at' => now()->subDays(10),
        'sent_at' => now()->subDays(9),
    ]);
    InboundMessage::factory()->create([
        'company_id' => $company->id,
        'kind' => InboundMessageKind::Reply,
        'received_at' => now()->subDays(8),
    ]);
    Interaction::factory()->create([
        'company_id' => $company->id,
        'kind' => InteractionKind::Call,
        'occurred_at' => now()->subDay(),
    ]);

    $this->get(route('companies.show', $company))
        ->assertInertia(fn (Assert $page) => $page
            ->has('timeline', 5)
            ->where('timeline.0.kind', 'interaction')
            ->where('timeline.1.kind', 'reply')
            ->where('timeline.2.kind', 'letter_sent')
            ->where('timeline.3.kind', 'letter_generated')
            ->where('timeline.4.kind', 'added')
        );
});

it('shows the reply itself rather than the reply and its timestamp', function () {
    $company = Company::factory()->create([
        'status' => 'replied',
        'replied_at' => now()->subDay(),
    ]);

    InboundMessage::factory()->create([
        'company_id' => $company->id,
        'kind' => InboundMessageKind::Reply,
        'received_at' => now()->subDay(),
    ]);

    $this->get(route('companies.show', $company))
        ->assertInertia(function (Assert $page) {
            $timeline = $page->toArray()['props']['timeline'];
            $replies = array_filter($timeline, fn ($e) => $e['kind'] === 'reply');

            expect($replies)->toHaveCount(1);
        });
});

it('falls back to the stamp when a status was set by hand', function () {
    $company = Company::factory()->create([
        'status' => 'replied',
        'replied_at' => now()->subDay(),
    ]);

    $this->get(route('companies.show', $company))
        ->assertInertia(function (Assert $page) {
            $timeline = $page->toArray()['props']['timeline'];
            $titles = array_column($timeline, 'title');

            expect($titles)->toContain('Marked replied');
        });
});

it('logs an interaction', function () {
    $company = Company::factory()->create();

    $this->post(route('interactions.store', $company), [
        'kind' => 'meeting',
        'occurred_at' => now()->subHour()->toDateTimeString(),
        'summary' => 'Koffie gedronken in Enschede.',
    ])->assertRedirect();

    expect($company->interactions()->sole())
        ->kind->toBe(InteractionKind::Meeting)
        ->summary->toBe('Koffie gedronken in Enschede.');
});

it('refuses an interaction in the future', function () {
    $company = Company::factory()->create();

    $this->post(route('interactions.store', $company), [
        'kind' => 'call',
        'occurred_at' => now()->addWeek()->toDateTimeString(),
        'summary' => 'Nog niet gebeurd.',
    ])->assertSessionHasErrors('occurred_at');

    expect($company->interactions()->count())->toBe(0);
});

it('requires a summary', function () {
    $company = Company::factory()->create();

    $this->post(route('interactions.store', $company), [
        'kind' => 'call',
        'occurred_at' => now()->toDateTimeString(),
        'summary' => '',
    ])->assertSessionHasErrors('summary');
});

it('removes an interaction', function () {
    $interaction = Interaction::factory()->create();

    $this->delete(route('interactions.destroy', $interaction))->assertRedirect();

    expect(Interaction::count())->toBe(0);
});

it('removes interactions with the company', function () {
    $company = Company::factory()->create();
    Interaction::factory()->create(['company_id' => $company->id]);

    $company->delete();

    expect(Interaction::count())->toBe(0);
});
