<?php

use App\Models\User;
use App\Services\Portal\IdPortalClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.thijssensoftware.base_url', 'https://id.thijssensoftware.nl');
    config()->set('services.thijssensoftware.client_id', 'test-client');
    config()->set('services.thijssensoftware.client_secret', 'test-secret');
});

it('returns an empty list when the id endpoint errors', function () {
    Http::fake(['*' => Http::response('', 500)]);

    expect(app(IdPortalClient::class)->appsFor(User::factory()->create()))->toBe([]);
});

it('returns an empty list when the id endpoint times out', function () {
    Http::fake(fn () => throw new ConnectionException('timed out'));

    expect(app(IdPortalClient::class)->appsFor(User::factory()->create()))->toBe([]);
});

it('returns the apps when the id endpoint responds', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'tok', 'expires_in' => 600]),
        '*/api/portal/apps' => Http::response(['applications' => [
            ['slug' => 'tracker', 'name' => 'Tracker', 'initials' => 'Tr', 'accent' => null, 'launch_url' => 'https://tracker.thijssensoftware.nl'],
        ]]),
    ]);

    $apps = app(IdPortalClient::class)->appsFor(User::factory()->create());

    expect($apps)->toHaveCount(1)
        ->and($apps[0]['slug'])->toBe('tracker')
        ->and($apps[0]['current'])->toBeFalse();
});
