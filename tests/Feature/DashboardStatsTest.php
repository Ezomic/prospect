<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows total and per-status company counts on the dashboard', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->count(3)->create(['status' => 'new']);
    Company::factory()->count(2)->create(['status' => 'sent']);
    Company::factory()->create(['status' => 'replied']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('total', 6)
            ->has('stats', 5)
            ->where('stats.0.value', 'new')
            ->where('stats.0.count', 3)
            ->where('stats.1.value', 'sent')
            ->where('stats.1.count', 2)
            ->where('stats.2.value', 'replied')
            ->where('stats.2.count', 1)
            ->where('stats.3.value', 'bounced')
            ->where('stats.3.count', 0)
        );
});

it('counts every status in a single grouped query', function () {
    $this->actingAs(User::factory()->create());

    Company::factory()->count(2)->create(['status' => 'new']);
    Company::factory()->create(['status' => 'sent']);

    $queries = 0;
    DB::listen(function (QueryExecuted $query) use (&$queries) {
        if (str_contains($query->sql, 'from "companies"')) {
            $queries++;
        }
    });

    $this->get(route('dashboard'))->assertOk();

    // One grouped aggregate for the status counts, one for the follow-ups due.
    expect($queries)->toBe(2);
});

it('reports zero for every status when there are no companies', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('total', 0)
            ->has('stats', 5)
            ->where('stats.0.count', 0)
        );
});
