<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('companies.import.create'))->assertRedirect(route('login'));
});

it('renders the import page with nothing previewed', function () {
    $this->get(route('companies.import.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Import')
            ->where('preview', null)
        );
});

it('previews rows without writing anything', function () {
    $csv = "name,email,city\nAcme BV,info@acme.example,Enschede\nGlobex NV,info@globex.example,Hengelo";

    $this->post(route('companies.import.preview'), ['csv' => $csv])
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.rows', 2)
            ->where('preview.rows.0.line', 2)
            ->where('preview.rows.0.values.name', 'Acme BV')
            ->where('preview.rows.0.values.city', 'Enschede')
            ->where('preview.rows.1.values.name', 'Globex NV')
        );

    expect(Company::count())->toBe(0);
});

it('accepts semicolon separated csv from dutch excel', function () {
    $csv = "name;email;city\nAcme BV;info@acme.example;Enschede";

    $this->post(route('companies.import.preview'), ['csv' => $csv])
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.rows.0.values.name', 'Acme BV')
            ->where('preview.rows.0.values.email', 'info@acme.example')
        );
});

it('matches headers loosely and reports the ones it ignored', function () {
    $csv = "Name,Contact Name,Turnover\nAcme BV,Jane Doe,1000000";

    $this->post(route('companies.import.preview'), ['csv' => $csv])
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.rows.0.values.contact_name', 'Jane Doe')
            ->where('preview.ignored', ['Turnover'])
        );
});

it('reports per-row validation errors', function () {
    $csv = "name,email\n,not-an-email\nAcme BV,info@acme.example";

    $this->post(route('companies.import.preview'), ['csv' => $csv])
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.rows.0.errors.name')
            ->has('preview.rows.0.errors.email')
            ->where('preview.rows.1.errors', [])
        );
});

it('flags a row that matches an existing company', function () {
    Company::factory()->create(['name' => 'Acme Holding', 'email' => 'info@acme.example']);

    $csv = "name,email\nAcme BV,INFO@acme.example";

    $this->post(route('companies.import.preview'), ['csv' => $csv])
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.rows.0.duplicate.name', 'Acme Holding')
            ->where('preview.rows.0.duplicate.matched_on', 'email')
        );
});

it('flags a row that duplicates an earlier row in the same paste', function () {
    $csv = "name,kvk_number\nAcme BV,12345678\nAcme Again,12345678";

    $this->post(route('companies.import.preview'), ['csv' => $csv])
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.rows.0.duplicate', null)
            ->where('preview.rows.1.duplicate.name', 'Acme BV')
        );
});

it('imports only the confirmed lines', function () {
    $csv = "name,email,source\nAcme BV,info@acme.example,directory\nGlobex NV,info@globex.example,referral";

    $this->post(route('companies.import.store'), ['csv' => $csv, 'lines' => [2]])
        ->assertRedirect(route('companies.index'));

    expect(Company::count())->toBe(1);

    $company = Company::sole();

    expect($company->name)->toBe('Acme BV')
        ->and($company->source)->toBe('directory')
        ->and($company->status)->toBe(CompanyStatus::New);
});

it('never imports a row with validation errors even if it is confirmed', function () {
    $csv = "name,email\n,info@acme.example";

    $this->post(route('companies.import.store'), ['csv' => $csv, 'lines' => [2]])
        ->assertRedirect();

    expect(Company::count())->toBe(0);
});

it('requires at least one confirmed line', function () {
    $this->post(route('companies.import.store'), ['csv' => "name\nAcme BV", 'lines' => []])
        ->assertSessionHasErrors('lines');

    expect(Company::count())->toBe(0);
});
