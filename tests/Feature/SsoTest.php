<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

/**
 * Signs an existing user in through the ID callback and returns the
 * remember-me cookie the callback set.
 *
 * @return array{string, string}
 */
function signInThroughId(): array
{
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn((new SocialiteUser)->map([
        'id' => '42',
        'name' => 'Robbin Thijssen',
        'email' => 'robbin@example.com',
    ]));
    Socialite::shouldReceive('driver')->with('thijssensoftware')->andReturn($provider);

    $recaller = Auth::guard()->getRecallerName();
    $cookie = test()->get(route('sso.callback'))->assertRedirect()->getCookie($recaller);

    expect($cookie)->not->toBeNull();

    return [$recaller, (string) $cookie?->getValue()];
}

/**
 * A browser coming back after its session expired, carrying nothing but the
 * remember-me cookie.
 *
 * @return TestResponse<Response>
 */
function returnWithRememberCookie(string $recaller, string $value): TestResponse
{
    Auth::forgetGuards();
    test()->flushSession();

    return test()->withCookie($recaller, $value)->get(route('dashboard'));
}

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

it('keeps a browser signed in through the remember-me cookie after an ID sign-in', function () {
    $user = User::factory()->create(['email' => 'robbin@example.com']);

    [$recaller, $value] = signInThroughId();

    returnWithRememberCookie($recaller, $value)->assertOk();

    $this->assertAuthenticatedAs($user->fresh());
});

it('refuses the remember-me cookie once ID signs the user out', function () {
    config(['id-client.logout_secret' => 'test-logout-secret']);

    User::factory()->create(['email' => 'robbin@example.com']);

    [$recaller, $value] = signInThroughId();

    returnWithRememberCookie($recaller, $value)->assertOk();

    $body = json_encode(['sub' => '42', 'issued_at' => Carbon::now()->getTimestamp()], JSON_THROW_ON_ERROR);

    $this->call('POST', route('sso.logout'), server: [
        'HTTP_X_ID_SIGNATURE' => hash_hmac('sha256', $body, 'test-logout-secret'),
        'CONTENT_TYPE' => 'application/json',
    ], content: $body)->assertOk();

    // Later, so the cookie itself is refused rather than a same-second stamp
    // ending the session it restores.
    $this->travel(1)->minute();

    returnWithRememberCookie($recaller, $value)->assertRedirect(route('login'));

    $this->assertGuest();
});
