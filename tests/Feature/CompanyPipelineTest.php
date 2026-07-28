<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('marks a company replied and stamps the timestamp', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['status' => 'sent', 'replied_at' => null]);

    $this->patch(route('companies.status', $company), ['status' => 'replied'])
        ->assertRedirect();

    expect($company->fresh())
        ->status->toBe(CompanyStatus::Replied)
        ->and($company->fresh()->replied_at)->not->toBeNull();
});

it('marks a company bounced and stamps the timestamp', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['status' => 'sent', 'bounced_at' => null]);

    $this->patch(route('companies.status', $company), ['status' => 'bounced'])
        ->assertRedirect();

    expect($company->fresh())
        ->status->toBe(CompanyStatus::Bounced)
        ->and($company->fresh()->bounced_at)->not->toBeNull();
});

it('validates the status transition', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create();

    $this->patch(route('companies.status', $company), ['status' => 'nonsense'])
        ->assertSessionHasErrors('status');
});

it('schedules and clears a follow-up', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['follow_up_at' => null]);

    $this->patch(route('companies.follow-up', $company), ['follow_up_at' => '2026-08-15'])
        ->assertRedirect();

    expect($company->fresh()->follow_up_at?->toDateString())->toBe('2026-08-15');

    $this->patch(route('companies.follow-up', $company), ['follow_up_at' => null])
        ->assertRedirect();

    expect($company->fresh()->follow_up_at)->toBeNull();
});

it('counts follow-ups due on the dashboard', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->create(['follow_up_at' => today()->subDay(), 'status' => 'sent']);
    Company::factory()->create(['follow_up_at' => today(), 'status' => 'replied']);
    Company::factory()->create(['follow_up_at' => today()->addWeek(), 'status' => 'sent']);
    Company::factory()->create(['follow_up_at' => today()->subDay(), 'status' => 'closed']);

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('followUpsDue', 2));
});
