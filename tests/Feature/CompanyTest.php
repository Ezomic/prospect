<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('redirects guests to the login page', function () {
    $this->get(route('companies.index'))->assertRedirect(route('login'));
});

it('renders the companies index for authenticated users', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('companies/Index'));
});

it('lists the companies on the index page', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->create(['name' => 'Acme BV']);
    Company::factory()->create(['name' => 'Globex NV']);

    $this->get(route('companies.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Index')
            ->has('companies', 2)
            ->where('companies.0.name', 'Acme BV')
            ->where('companies.1.name', 'Globex NV')
        );
});

it('renders the company detail page', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['name' => 'Acme BV']);

    $this->get(route('companies.show', $company))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Show')
            ->where('company.id', $company->id)
            ->where('company.name', 'Acme BV')
        );
});

it('creates a company', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('companies.store'), [
        'name' => 'Acme BV',
        'website' => 'acme.example',
        'email' => 'info@acme.example',
        'contact_name' => 'Jane Doe',
        'city' => 'Amsterdam',
        'kvk_number' => '12345678',
        'industry' => 'Software',
        'status' => 'new',
        'notes' => 'Met them at a meetup.',
    ])->assertRedirect(route('companies.index'));

    $this->assertDatabaseHas('companies', [
        'name' => 'Acme BV',
        'city' => 'Amsterdam',
        'status' => 'new',
    ]);
});

it('validates a company on create', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('companies.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'status' => 'invalid',
    ])->assertSessionHasErrors(['name', 'email', 'status']);

    expect(Company::count())->toBe(0);
});

it('updates a company', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['name' => 'Old Name', 'status' => 'new']);

    $this->put(route('companies.update', $company), [
        'name' => 'New Name',
        'website' => null,
        'email' => null,
        'contact_name' => null,
        'city' => null,
        'kvk_number' => null,
        'industry' => null,
        'status' => 'sent',
        'notes' => null,
    ])->assertRedirect(route('companies.index'));

    expect($company->fresh())
        ->name->toBe('New Name')
        ->status->toBe(CompanyStatus::Sent);
});

it('deletes a company', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create();

    $this->delete(route('companies.destroy', $company))
        ->assertRedirect(route('companies.index'));

    $this->assertDatabaseMissing('companies', ['id' => $company->id]);
});
