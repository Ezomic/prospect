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
        ->and($letter->body)->toContain('Software')
        ->and($letter->email_subject)->not->toBeNull()
        ->and($letter->email_body)->toContain('Jane Doe')
        ->and($letter->email_body)->toContain('Acme BV');
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
            ->has('statuses', 2)
        );
});

it('renders a letter as a pdf', function () {
    $this->actingAs(User::factory()->create());

    $letter = Letter::factory()->create();

    $response = $this->get(route('letters.pdf', $letter));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('updates a letter', function () {
    $this->actingAs(User::factory()->create());

    $letter = Letter::factory()->create(['status' => 'draft']);

    $this->put(route('letters.update', $letter), [
        'subject' => 'Nieuwe onderwerpregel',
        'body' => 'Aangepaste inhoud van de brief.',
        'email_subject' => 'Nieuw e-mailonderwerp',
        'email_body' => 'Aangepaste begeleidende e-mail.',
        'status' => 'ready',
    ])->assertRedirect(route('companies.show', $letter->company_id));

    expect($letter->fresh())
        ->subject->toBe('Nieuwe onderwerpregel')
        ->email_subject->toBe('Nieuw e-mailonderwerp')
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

it('rejects setting a letter to sent from the edit form', function () {
    $this->actingAs(User::factory()->create());

    $letter = Letter::factory()->create(['status' => 'draft']);

    $this->put(route('letters.update', $letter), [
        'subject' => 'Subject',
        'body' => 'Body',
        'email_subject' => 'Email subject',
        'email_body' => 'Email body',
        'status' => 'sent',
    ])->assertSessionHasErrors('status');

    expect($letter->fresh())
        ->status->toBe(LetterStatus::Draft)
        ->sent_at->toBeNull();
});

it('does not offer sent as an editable status', function () {
    $this->actingAs(User::factory()->create());

    $letter = Letter::factory()->create();

    $this->get(route('letters.edit', $letter))
        ->assertInertia(fn (Assert $page) => $page
            ->has('statuses', 2)
            ->where('statuses.0.value', 'draft')
            ->where('statuses.1.value', 'ready')
        );
});

it('refuses to edit a letter that was already sent', function () {
    $this->actingAs(User::factory()->create());

    $letter = Letter::factory()->create([
        'subject' => 'Original subject',
        'status' => 'sent',
        'sent_at' => now()->subDay(),
    ]);

    $this->put(route('letters.update', $letter), [
        'subject' => 'Rewritten subject',
        'body' => 'Body',
        'email_subject' => 'Email subject',
        'email_body' => 'Email body',
        'status' => 'draft',
    ])->assertRedirect();

    expect($letter->fresh())
        ->subject->toBe('Original subject')
        ->status->toBe(LetterStatus::Sent);
});

it('warns about other companies sharing the recipient address', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['name' => 'Acme BV', 'email' => 'info@acme.example']);
    Company::factory()->create(['name' => 'Acme Holding', 'email' => 'info@acme.example']);
    Company::factory()->create(['name' => 'Globex', 'email' => 'info@globex.example']);

    $letter = Letter::factory()->create(['company_id' => $company->id]);

    $this->get(route('letters.edit', $letter))
        ->assertInertia(fn (Assert $page) => $page
            ->where('duplicateCompanies', ['Acme Holding'])
        );
});

it('reports no duplicates when the address is unique', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['email' => 'info@acme.example']);
    $letter = Letter::factory()->create(['company_id' => $company->id]);

    $this->get(route('letters.edit', $letter))
        ->assertInertia(fn (Assert $page) => $page->where('duplicateCompanies', []));
});

it('previews the message exactly as it will be delivered', function () {
    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['email' => 'hr@acme.example']);
    $letter = Letter::factory()->create([
        'company_id' => $company->id,
        'email_subject' => 'Open aanbod - Robbin Thijssen',
        'email_body' => "Beste Jane Doe,\n\nBijgaand mijn open aanbod.",
    ]);

    $this->get(route('letters.edit', $letter))
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.to', 'hr@acme.example')
            ->where('preview.subject', 'Open aanbod - Robbin Thijssen')
            ->where('preview.body', "Beste Jane Doe,\n\nBijgaand mijn open aanbod.")
            ->where('preview.attachments', ["brief-{$letter->id}.pdf"])
            ->has('preview.from')
        );
});

it('previews the subject the mail actually falls back to', function () {
    $this->actingAs(User::factory()->create());

    $letter = Letter::factory()->create([
        'subject' => 'Brief onderwerp',
        'email_subject' => null,
    ]);

    $this->get(route('letters.edit', $letter))
        ->assertInertia(fn (Assert $page) => $page->where('preview.subject', 'Brief onderwerp'));
});
