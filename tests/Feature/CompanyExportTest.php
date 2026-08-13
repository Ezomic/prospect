<?php

use App\Actions\Companies\ParseCompanyCsv;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function exportCsv(array $query = []): string
{
    $response = test()->get(route('companies.export', $query));

    $response->assertOk();

    return $response->streamedContent();
}

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('companies.export'))->assertRedirect(route('login'));
});

it('writes exactly the headers the importer recognises', function () {
    Company::factory()->create();

    $header = strtok(exportCsv(), "\n");

    // A file the importer cannot read back is not a round trip.
    expect(trim($header))->toBe(implode(',', ParseCompanyCsv::COLUMNS));
});

it('exports the companies with their values', function () {
    Company::factory()->create([
        'name' => 'Acme BV',
        'email' => 'info@acme.example',
        'city' => 'Enschede',
    ]);

    $csv = exportCsv();

    expect($csv)->toContain('Acme BV')
        ->toContain('info@acme.example')
        ->toContain('Enschede');
});

it('exports only the filtered view', function () {
    Company::factory()->create(['name' => 'Reachable BV', 'email' => 'info@acme.example']);
    Company::factory()->create(['name' => 'Unreachable NV', 'email' => null]);

    $csv = exportCsv(['missing_email' => 1]);

    expect($csv)->toContain('Unreachable NV')
        ->not->toContain('Reachable BV');
});

it('honours the search filter', function () {
    Company::factory()->create(['name' => 'Acme BV', 'city' => 'Enschede']);
    Company::factory()->create(['name' => 'Globex NV', 'city' => 'Hengelo']);

    expect(exportCsv(['search' => 'Enschede']))
        ->toContain('Acme BV')
        ->not->toContain('Globex NV');
});

it('round-trips back through the importer', function () {
    Company::factory()->create([
        'name' => 'Acme BV',
        'email' => 'info@acme.example',
        'city' => 'Enschede',
        'kvk_number' => '12345678',
    ]);

    $csv = exportCsv();

    // Re-importing its own export should recognise every column and flag the
    // row as a duplicate of the company it came from.
    $parsed = app(ParseCompanyCsv::class)->handle($csv);

    expect($parsed['ignored'])->toBe([])
        ->and($parsed['rows'])->toHaveCount(1)
        ->and($parsed['rows'][0]['errors'])->toBe([])
        ->and($parsed['rows'][0]['values']['name'])->toBe('Acme BV')
        ->and($parsed['rows'][0]['duplicate']['name'])->toBe('Acme BV');
});
