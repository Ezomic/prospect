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

it('returns an empty result when the id endpoint errors', function () {
    Http::fake(['*' => Http::response('', 500)]);

    expect(app(IdPortalClient::class)->appsFor(User::factory()->create()))->toBe(['apps' => [], 'categories' => []]);
});

it('returns an empty result when the id endpoint times out', function () {
    Http::fake(fn () => throw new ConnectionException('timed out'));

    expect(app(IdPortalClient::class)->appsFor(User::factory()->create()))->toBe(['apps' => [], 'categories' => []]);
});

it('returns the apps when the id endpoint responds', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'tok', 'expires_in' => 600]),
        '*/api/portal/apps' => Http::response(['applications' => [
            ['slug' => 'tracker', 'name' => 'Tracker', 'initials' => 'Tr', 'accent' => null, 'launch_url' => 'https://tracker.thijssensoftware.nl'],
        ]]),
    ]);

    $result = app(IdPortalClient::class)->appsFor(User::factory()->create());

    expect($result['apps'])->toHaveCount(1)
        ->and($result['categories'])->toBe([])
        ->and($result['apps'][0]['slug'])->toBe('tracker')
        ->and($result['apps'][0]['current'])->toBeFalse();
});

it('groups categorized apps such as games', function () {
    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'tok', 'expires_in' => 600]),
        '*/api/portal/apps' => Http::response([
            'applications' => [
                ['slug' => 'tracker', 'name' => 'Tracker', 'initials' => 'Tr', 'accent' => null, 'launch_url' => 'https://tracker.thijssensoftware.nl'],
            ],
            'categories' => [
                [
                    'category' => 'Games',
                    'apps' => [
                        ['slug' => 'chess', 'name' => 'Chess', 'initials' => 'C', 'accent' => null, 'launch_url' => 'https://chess.thijssensoftware.nl'],
                    ],
                ],
            ],
        ]),
    ]);

    $result = app(IdPortalClient::class)->appsFor(User::factory()->create());

    expect($result['apps'])->toHaveCount(1)
        ->and($result['categories'])->toHaveCount(1)
        ->and($result['categories'][0]['category'])->toBe('Games')
        ->and($result['categories'][0]['apps'][0]['slug'])->toBe('chess');
});
