<?php

use App\Models\Company;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('marks a company do not contact with a reason and a timestamp', function () {
    $company = Company::factory()->create(['do_not_contact' => false]);

    $this->patch(route('companies.do-not-contact', $company), [
        'do_not_contact' => true,
        'reason' => 'Vroeg per mail om verwijderd te worden.',
    ])->assertRedirect();

    expect($company->fresh())
        ->do_not_contact->toBeTrue()
        ->do_not_contact_reason->toBe('Vroeg per mail om verwijderd te worden.')
        ->and($company->fresh()->do_not_contact_at)->not->toBeNull();
});

it('drops the company out of follow-ups so a reminder cannot bring it back', function () {
    $company = Company::factory()->create([
        'status' => 'sent',
        'follow_up_at' => today(),
        'do_not_contact' => false,
    ]);

    $this->patch(route('companies.do-not-contact', $company), ['do_not_contact' => true]);

    expect($company->fresh()->follow_up_at)->toBeNull();

    $this->get(route('follow-ups.index'))
        ->assertInertia(fn (Assert $page) => $page->where('followUps.total', 0));
});

it('leaves a flagged company out of the follow-ups page even if a date lingers', function () {
    Company::factory()->create([
        'status' => 'sent',
        'follow_up_at' => today(),
        'do_not_contact' => true,
    ]);

    $this->get(route('follow-ups.index'))
        ->assertInertia(fn (Assert $page) => $page->where('followUps.total', 0));
});

it('does not count a flagged company as a follow-up due on the dashboard', function () {
    Company::factory()->create([
        'status' => 'sent',
        'follow_up_at' => today(),
        'do_not_contact' => true,
    ]);

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('followUpsDue', 0));
});

it('allows contact again and clears the reason', function () {
    $company = Company::factory()->create([
        'do_not_contact' => true,
        'do_not_contact_at' => now(),
        'do_not_contact_reason' => 'Eerder afgemeld.',
    ]);

    $this->patch(route('companies.do-not-contact', $company), ['do_not_contact' => false])
        ->assertRedirect();

    expect($company->fresh())
        ->do_not_contact->toBeFalse()
        ->do_not_contact_at->toBeNull()
        ->do_not_contact_reason->toBeNull();
});

it('refuses to send to a flagged company', function () {
    Storage::fake('local');
    Mail::fake();
    Queue::fake();

    $company = Company::factory()->create(['email' => 'hr@acme.example']);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->patch(route('companies.do-not-contact', $company), ['do_not_contact' => true]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Queue::assertNothingPushed();
    expect($letter->fresh()->sent_at)->toBeNull();
});

it('cannot be set through the general company form', function () {
    $company = Company::factory()->create(['do_not_contact' => false]);

    $this->put(route('companies.update', $company), [
        'name' => $company->name,
        'status' => 'new',
        'do_not_contact' => true,
    ])->assertRedirect();

    expect($company->fresh()->do_not_contact)->toBeFalse();
});

it('requires the flag', function () {
    $company = Company::factory()->create();

    $this->patch(route('companies.do-not-contact', $company), [])
        ->assertSessionHasErrors('do_not_contact');
});
