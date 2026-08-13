<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('sets a status on the selected companies only', function () {
    $selected = Company::factory()->count(2)->create(['status' => 'new']);
    $untouched = Company::factory()->create(['status' => 'new']);

    $this->post(route('companies.bulk'), [
        'ids' => $selected->pluck('id')->all(),
        'action' => 'status',
        'status' => 'closed',
    ])->assertRedirect();

    expect($selected->first()->fresh()->status)->toBe(CompanyStatus::Closed)
        ->and($untouched->fresh()->status)->toBe(CompanyStatus::New);
});

it('stamps replied_at when bulk setting replied', function () {
    $company = Company::factory()->create(['status' => 'sent', 'replied_at' => null]);

    $this->post(route('companies.bulk'), [
        'ids' => [$company->id],
        'action' => 'status',
        'status' => 'replied',
    ]);

    expect($company->fresh()->replied_at)->not->toBeNull();
});

it('marks do not contact with the same fields as the single action', function () {
    $company = Company::factory()->create([
        'do_not_contact' => false,
        'follow_up_at' => today()->addWeek(),
    ]);

    $this->post(route('companies.bulk'), [
        'ids' => [$company->id],
        'action' => 'do_not_contact',
        'reason' => 'Batch afgemeld.',
    ]);

    expect($company->fresh())
        ->do_not_contact->toBeTrue()
        ->do_not_contact_reason->toBe('Batch afgemeld.')
        ->follow_up_at->toBeNull()
        ->and($company->fresh()->do_not_contact_at)->not->toBeNull();
});

it('clears follow-ups and reports the ones that had none', function () {
    $withReminder = Company::factory()->create(['follow_up_at' => today()]);
    $without = Company::factory()->create(['follow_up_at' => null]);

    $this->post(route('companies.bulk'), [
        'ids' => [$withReminder->id, $without->id],
        'action' => 'clear_follow_up',
    ])->assertRedirect();

    expect($withReminder->fresh()->follow_up_at)->toBeNull();
});

it('generates drafts and never sends', function () {
    $companies = Company::factory()->count(3)->create(['email' => 'hr@acme.example']);

    $this->post(route('companies.bulk'), [
        'ids' => $companies->pluck('id')->all(),
        'action' => 'generate_letter',
    ])->assertRedirect();

    expect(Letter::count())->toBe(3)
        // Sending stays a deliberate per-letter act.
        ->and(Letter::whereNotNull('sent_at')->count())->toBe(0);
});

it('skips generating for a company marked do not contact', function () {
    $ok = Company::factory()->create(['do_not_contact' => false]);
    $blocked = Company::factory()->create(['do_not_contact' => true]);

    $this->post(route('companies.bulk'), [
        'ids' => [$ok->id, $blocked->id],
        'action' => 'generate_letter',
    ]);

    expect($ok->letters()->count())->toBe(1)
        ->and($blocked->letters()->count())->toBe(0);
});

it('requires at least one company', function () {
    $this->post(route('companies.bulk'), ['ids' => [], 'action' => 'status', 'status' => 'new'])
        ->assertSessionHasErrors('ids');
});

it('rejects an action it does not know', function () {
    $company = Company::factory()->create();

    $this->post(route('companies.bulk'), ['ids' => [$company->id], 'action' => 'delete_everything'])
        ->assertSessionHasErrors('action');
});

it('rejects an id that does not exist', function () {
    $this->post(route('companies.bulk'), ['ids' => [99999], 'action' => 'clear_follow_up'])
        ->assertSessionHasErrors('ids.0');
});

it('requires a status when setting one', function () {
    $company = Company::factory()->create();

    $this->post(route('companies.bulk'), ['ids' => [$company->id], 'action' => 'status'])
        ->assertSessionHasErrors('status');
});
