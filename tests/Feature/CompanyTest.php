<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Letter;
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
            ->has('companies.data', 2)
            ->where('companies.data.0.name', 'Acme BV')
            ->where('companies.data.1.name', 'Globex NV')
        );
});

it('filters companies by search term', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->create(['name' => 'Acme BV', 'city' => 'Amsterdam']);
    Company::factory()->create(['name' => 'Globex NV', 'city' => 'Rotterdam']);

    $this->get(route('companies.index', ['search' => 'Amsterdam']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('companies.data', 1)
            ->where('companies.data.0.name', 'Acme BV')
            ->where('filters.search', 'Amsterdam')
        );
});

it('filters companies by status', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->create(['name' => 'Sent Co', 'status' => 'sent']);
    Company::factory()->create(['name' => 'New Co', 'status' => 'new']);

    $this->get(route('companies.index', ['status' => 'sent']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('companies.data', 1)
            ->where('companies.data.0.name', 'Sent Co')
            ->where('filters.status', 'sent')
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

it('stores lead qualification fields', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('companies.store'), [
        'name' => 'Studio X',
        'website' => null,
        'email' => null,
        'contact_name' => 'Jane Dev',
        'city' => null,
        'kvk_number' => null,
        'industry' => 'Agency',
        'status' => 'new',
        'notes' => null,
        'source' => 'vacancy',
        'contact_role' => 'Lead developer',
        'linkedin_url' => 'https://www.linkedin.com/in/janedev',
        'lead_score' => 8,
        'first_contact_channel' => 'linkedin',
    ])->assertRedirect(route('companies.index'));

    $this->assertDatabaseHas('companies', [
        'name' => 'Studio X',
        'source' => 'vacancy',
        'contact_role' => 'Lead developer',
        'lead_score' => 8,
        'first_contact_channel' => 'linkedin',
    ]);
});

it('rejects an out-of-range lead score', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('companies.store'), [
        'name' => 'Studio X',
        'status' => 'new',
        'lead_score' => 42,
    ])->assertSessionHasErrors('lead_score');
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

it('paginates the companies index', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->count(30)->create();

    $this->get(route('companies.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('companies.data', 25)
            ->where('companies.total', 30)
            ->where('companies.last_page', 2)
        );

    $this->get(route('companies.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('companies.data', 5));
});

it('keeps the filters when paging', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->count(30)->create(['status' => 'sent', 'city' => 'Enschede']);
    Company::factory()->count(3)->create(['status' => 'new', 'city' => 'Utrecht']);

    $this->get(route('companies.index', ['status' => 'sent', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('companies.total', 30)
            ->where('filters.status', 'sent')
        );
});

it('sorts by an allowed column and direction', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->create(['name' => 'Acme BV', 'lead_score' => 2]);
    Company::factory()->create(['name' => 'Globex NV', 'lead_score' => 9]);

    $this->get(route('companies.index', ['sort' => 'lead_score', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('companies.data.0.name', 'Globex NV')
            ->where('filters.sort', 'lead_score')
            ->where('filters.direction', 'desc')
        );
});

it('sorts by the date of the last letter sent', function () {
    $this->actingAs(User::factory()->create());

    $recent = Company::factory()->create(['name' => 'Recent BV']);
    $old = Company::factory()->create(['name' => 'Old NV']);

    Letter::factory()->create(['company_id' => $recent->id, 'sent_at' => now()->subDay()]);
    Letter::factory()->create(['company_id' => $old->id, 'sent_at' => now()->subYear()]);

    $this->get(route('companies.index', ['sort' => 'last_contact', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('companies.data.0.name', 'Recent BV')
            ->where('companies.data.1.name', 'Old NV')
        );
});

it('rejects a sort column that is not on the allowlist', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('companies.index', ['sort' => 'password']))
        ->assertSessionHasErrors('sort');

    $this->get(route('companies.index', ['direction' => 'drop table']))
        ->assertSessionHasErrors('direction');
});

it('stores a lead score sent as a number', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('companies.store'), [
        'name' => 'Acme BV',
        'status' => 'new',
        'lead_score' => 8,
    ])->assertRedirect(route('companies.index'));

    expect(Company::sole()->lead_score)->toBe(8);
});

it('stores a null lead score when the field is left empty', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('companies.store'), [
        'name' => 'Acme BV',
        'status' => 'new',
        'lead_score' => null,
    ])->assertRedirect(route('companies.index'));

    expect(Company::sole()->lead_score)->toBeNull();
});
