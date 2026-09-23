<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the landing page to a visitor who is not signed in', function (): void {
    get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

/**
 * Prospect shipped for months without this, inherited from a clone that dropped it:
 * a signed-in user hitting / got the front door instead of the pipeline.
 */
it('sends a signed-in user straight to the dashboard', function (): void {
    actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
