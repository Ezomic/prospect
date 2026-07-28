<?php

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
