<?php

use App\Models\Company;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('requires authentication', function () {
    $this->get(route('follow-ups.index'))->assertRedirect(route('login'));
});

it('lists only companies with a reminder, oldest first', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->create(['name' => 'Later BV', 'status' => 'sent', 'follow_up_at' => today()->addWeek()]);
    Company::factory()->create(['name' => 'Overdue NV', 'status' => 'sent', 'follow_up_at' => today()->subWeek()]);
    Company::factory()->create(['name' => 'No reminder', 'status' => 'sent', 'follow_up_at' => null]);

    $this->get(route('follow-ups.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('follow-ups/Index')
            ->has('followUps.data', 2)
            ->where('followUps.data.0.name', 'Overdue NV')
            ->where('followUps.data.1.name', 'Later BV')
        );
});

it('leaves closed companies out', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->create(['status' => 'closed', 'follow_up_at' => today()]);

    $this->get(route('follow-ups.index'))
        ->assertInertia(fn (Assert $page) => $page->where('followUps.total', 0));
});

it('groups each reminder as overdue, today or upcoming', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->create(['name' => 'Past', 'status' => 'sent', 'follow_up_at' => today()->subDay()]);
    Company::factory()->create(['name' => 'Now', 'status' => 'sent', 'follow_up_at' => today()]);
    Company::factory()->create(['name' => 'Soon', 'status' => 'sent', 'follow_up_at' => today()->addDay()]);

    $this->get(route('follow-ups.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('followUps.data.0.group', 'overdue')
            ->where('followUps.data.1.group', 'today')
            ->where('followUps.data.2.group', 'upcoming')
        );
});

it('reports when the last letter went out', function () {
    // Frozen: the assertion recomputes now(), so without this the test fails
    // whenever the second ticks over between creating the letter and asserting.
    $this->freezeTime();

    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['status' => 'sent', 'follow_up_at' => today()]);
    Letter::factory()->create(['company_id' => $company->id, 'sent_at' => now()->subMonth()]);
    Letter::factory()->create(['company_id' => $company->id, 'sent_at' => now()->subWeek()]);

    $this->get(route('follow-ups.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('followUps.data.0.last_contact_at', now()->subWeek()->toDateTimeString())
        );
});

it('snoozes an upcoming reminder by a week from its own date', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['status' => 'sent', 'follow_up_at' => today()->addDays(3)]);

    $this->patch(route('follow-ups.snooze', $company))->assertRedirect();

    expect($company->fresh()->follow_up_at->toDateString())
        ->toBe(today()->addDays(10)->toDateString());
});

it('snoozes an overdue reminder from today so it does not stay overdue', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['status' => 'sent', 'follow_up_at' => today()->subMonth()]);

    $this->patch(route('follow-ups.snooze', $company))->assertRedirect();

    expect($company->fresh()->follow_up_at->toDateString())
        ->toBe(today()->addWeek()->toDateString());
});

it('paginates the follow-ups', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->count(30)->create(['status' => 'sent', 'follow_up_at' => today()]);

    $this->get(route('follow-ups.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('followUps.data', 25)
            ->where('followUps.total', 30)
        );
});
