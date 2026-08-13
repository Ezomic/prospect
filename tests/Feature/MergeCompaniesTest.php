<?php

use App\Enums\CompanyStatus;
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

it('offers only companies that look like duplicates', function () {
    $company = Company::factory()->create(['name' => 'Acme BV', 'email' => 'info@acme.example']);
    Company::factory()->create(['name' => 'Acme Holding', 'email' => 'info@acme.example']);
    Company::factory()->create(['name' => 'Globex', 'email' => 'info@globex.example']);

    $this->get(route('companies.merge', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Merge')
            ->has('candidates', 1)
            ->where('candidates.0.name', 'Acme Holding')
        );
});

it('moves letters, messages and interactions onto the survivor', function () {
    $survivor = Company::factory()->create(['email' => 'info@acme.example']);
    $duplicate = Company::factory()->create(['email' => 'info@acme.example']);

    Letter::factory()->create(['company_id' => $duplicate->id]);
    Interaction::factory()->create(['company_id' => $duplicate->id]);
    InboundMessage::factory()->create(['company_id' => $duplicate->id]);

    $this->post(route('companies.merge.apply', $survivor), ['duplicate_id' => $duplicate->id])
        ->assertRedirect(route('companies.show', $survivor->id));

    expect($survivor->letters()->count())->toBe(1)
        ->and($survivor->interactions()->count())->toBe(1)
        ->and($survivor->inboundMessages()->count())->toBe(1)
        ->and(Company::find($duplicate->id))->toBeNull();
});

it('keeps the furthest-along status', function () {
    $survivor = Company::factory()->create(['email' => 'info@acme.example', 'status' => 'new']);
    $duplicate = Company::factory()->create(['email' => 'info@acme.example', 'status' => 'sent']);

    $this->post(route('companies.merge.apply', $survivor), ['duplicate_id' => $duplicate->id]);

    // Merging a contacted company into a fresh one must not lose the contact.
    expect($survivor->fresh()->status)->toBe(CompanyStatus::Sent);
});

it('keeps the earliest contact stamps', function () {
    $early = now()->subMonths(2);

    $survivor = Company::factory()->create([
        'email' => 'info@acme.example',
        'status' => 'replied',
        'replied_at' => now()->subDay(),
    ]);
    $duplicate = Company::factory()->create([
        'email' => 'info@acme.example',
        'status' => 'replied',
        'replied_at' => $early,
    ]);

    $this->post(route('companies.merge.apply', $survivor), ['duplicate_id' => $duplicate->id]);

    expect($survivor->fresh()->replied_at->toDateString())->toBe($early->toDateString());
});

it('keeps do-not-contact sticky across a merge', function () {
    $survivor = Company::factory()->create([
        'email' => 'info@acme.example',
        'do_not_contact' => false,
    ]);
    $duplicate = Company::factory()->create([
        'email' => 'info@acme.example',
        'do_not_contact' => true,
        'do_not_contact_at' => now()->subWeek(),
        'do_not_contact_reason' => 'Vroeg om verwijdering.',
    ]);

    $this->post(route('companies.merge.apply', $survivor), ['duplicate_id' => $duplicate->id]);

    // Merging must never quietly permit contact again.
    expect($survivor->fresh())
        ->do_not_contact->toBeTrue()
        ->do_not_contact_reason->toBe('Vroeg om verwijdering.')
        ->follow_up_at->toBeNull();
});

it('fills blanks on the survivor from the duplicate', function () {
    $survivor = Company::factory()->create([
        'email' => 'info@acme.example',
        'city' => null,
        'kvk_number' => null,
    ]);
    $duplicate = Company::factory()->create([
        'email' => 'info@acme.example',
        'city' => 'Enschede',
        'kvk_number' => '12345678',
    ]);

    $this->post(route('companies.merge.apply', $survivor), ['duplicate_id' => $duplicate->id]);

    expect($survivor->fresh())
        ->city->toBe('Enschede')
        ->kvk_number->toBe('12345678');
});

it('takes the chosen field from the duplicate', function () {
    $survivor = Company::factory()->create(['email' => 'info@acme.example', 'name' => 'Acme BV']);
    $duplicate = Company::factory()->create(['email' => 'info@acme.example', 'name' => 'Acme Holding BV']);

    $this->post(route('companies.merge.apply', $survivor), [
        'duplicate_id' => $duplicate->id,
        'take_from_duplicate' => ['name'],
    ]);

    expect($survivor->fresh()->name)->toBe('Acme Holding BV');
});

it('keeps the survivor value when nothing is chosen', function () {
    $survivor = Company::factory()->create(['email' => 'info@acme.example', 'city' => 'Enschede']);
    $duplicate = Company::factory()->create(['email' => 'info@acme.example', 'city' => 'Hengelo']);

    $this->post(route('companies.merge.apply', $survivor), ['duplicate_id' => $duplicate->id]);

    expect($survivor->fresh()->city)->toBe('Enschede');
});

it('refuses to merge a company into itself', function () {
    $company = Company::factory()->create();

    $this->post(route('companies.merge.apply', $company), ['duplicate_id' => $company->id])
        ->assertSessionHasErrors('duplicate_id');

    expect(Company::find($company->id))->not->toBeNull();
});

it('rejects a field that is not mergeable', function () {
    $survivor = Company::factory()->create(['email' => 'info@acme.example']);
    $duplicate = Company::factory()->create(['email' => 'info@acme.example']);

    $this->post(route('companies.merge.apply', $survivor), [
        'duplicate_id' => $duplicate->id,
        'take_from_duplicate' => ['status'],
    ])->assertSessionHasErrors('take_from_duplicate.0');
});
