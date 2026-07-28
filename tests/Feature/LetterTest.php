<?php

use App\Enums\LetterStatus;
use App\Models\Company;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('generates a draft letter for a company from a template', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create([
        'name' => 'Acme BV',
        'contact_name' => 'Jane Doe',
        'city' => 'Amsterdam',
        'industry' => 'Software',
    ]);

    $this->post(route('letters.store', $company))
        ->assertRedirect();

    $letter = $company->letters()->sole();

    expect($letter->status)->toBe(LetterStatus::Draft)
        ->and($letter->generated_at)->not->toBeNull()
        ->and($letter->subject)->toContain('Acme BV')
        ->and($letter->body)->toContain('Jane Doe')
        ->and($letter->body)->toContain('Amsterdam')
        ->and($letter->body)->toContain('Software');
});

it('falls back gracefully when company fields are missing', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create([
        'name' => 'Acme BV',
        'contact_name' => null,
        'city' => null,
        'industry' => null,
    ]);

    $this->post(route('letters.store', $company))->assertRedirect();

    $letter = $company->letters()->sole();

    expect($letter->body)->toContain('Geachte heer, mevrouw')
        ->and($letter->body)->toContain('Acme BV');
});

it('renders the letter edit page', function () {
    $this->actingAs(User::factory()->create());

    $letter = Letter::factory()->create();

    $this->get(route('letters.edit', $letter))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('letters/Edit')
            ->where('letter.id', $letter->id)
            ->has('letter.company')
            ->has('statuses', 3)
        );
});

it('updates a letter', function () {
    $this->actingAs(User::factory()->create());

    $letter = Letter::factory()->create(['status' => 'draft']);

    $this->put(route('letters.update', $letter), [
        'subject' => 'Nieuwe onderwerpregel',
        'body' => 'Aangepaste inhoud van de brief.',
        'status' => 'ready',
    ])->assertRedirect(route('companies.show', $letter->company_id));

    expect($letter->fresh())
        ->subject->toBe('Nieuwe onderwerpregel')
        ->status->toBe(LetterStatus::Ready);
});

it('deletes a letter', function () {
    $this->actingAs(User::factory()->create());

    $letter = Letter::factory()->create();

    $this->delete(route('letters.destroy', $letter))
        ->assertRedirect(route('companies.show', $letter->company_id));

    $this->assertDatabaseMissing('letters', ['id' => $letter->id]);
});

it('includes letters on the company detail page', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create();
    Letter::factory()->count(2)->create(['company_id' => $company->id]);

    $this->get(route('companies.show', $company))
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/Show')
            ->has('letters', 2)
        );
});
