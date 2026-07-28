<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('redirects to the thijssensoftware identity provider', function () {
    $response = $this->get(route('sso.redirect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toContain('id.thijssensoftware.nl/oauth/authorize');
});

it('shares an empty portal app list when sso is not configured', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('portalApps', []));
});

it('does not expose portal apps to guests', function () {
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('portalApps', []));
});
