<?php

use App\Models\Company;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('requires authentication', function () {
    $this->get(route('letter-template.edit'))->assertRedirect(route('login'));
});

it('renders the template with the placeholders filled in for a sample company', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('letter-template.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/LetterTemplate')
            ->has('template.subject')
            ->has('defaults.body')
            ->where('sample.company', 'Acme BV')
            ->where('sample.greeting', 'Beste Jane Doe')
        );

    expect(Company::count())->toBe(0);
});

it('saves an edited template', function () {
    $this->actingAs(User::factory()->create());

    $this->patch(route('letter-template.update'), [
        'subject' => 'Nieuw onderwerp voor {{ company }}',
        'body' => 'Body voor {{ company }}',
        'email_subject' => 'Mail onderwerp',
        'email_body' => 'Mail body',
    ])->assertRedirect(route('letter-template.edit'));

    expect(LetterTemplate::current()->subject)->toBe('Nieuw onderwerp voor {{ company }}')
        ->and(LetterTemplate::count())->toBe(1);
});

it('rejects an empty template', function () {
    $this->actingAs(User::factory()->create());

    $this->patch(route('letter-template.update'), [
        'subject' => '',
        'body' => '',
        'email_subject' => '',
        'email_body' => '',
    ])->assertSessionHasErrors(['subject', 'body', 'email_subject', 'email_body']);
});

it('generates a letter from the stored template', function () {
    $this->actingAs(User::factory()->create());

    LetterTemplate::current()->update([
        'subject' => 'Voorstel voor {{ company }}',
        'body' => '{{ greeting }}, {{ opening }} Groeten uit {{ city }}.',
        'email_subject' => 'Mail over {{ company }}',
        'email_body' => '{{ greeting }}, zie bijlage voor {{ company }}.',
    ]);

    $company = Company::factory()->create([
        'name' => 'Acme BV',
        'contact_name' => 'Jane Doe',
        'city' => 'Enschede',
        'industry' => 'software',
    ]);

    $this->post(route('letters.store', $company))->assertRedirect();

    $letter = Letter::sole();

    expect($letter->subject)->toBe('Voorstel voor Acme BV')
        ->and($letter->body)->toBe('Beste Jane Doe, Acme BV in Enschede viel mij op binnen de software. Groeten uit Enschede.')
        ->and($letter->email_subject)->toBe('Mail over Acme BV')
        ->and($letter->email_body)->toBe('Beste Jane Doe, zie bijlage voor Acme BV.');
});

it('keeps the conditional greeting and opening when details are missing', function () {
    $this->actingAs(User::factory()->create());

    LetterTemplate::current()->update([
        'subject' => 'x',
        'body' => '{{ greeting }} | {{ opening }}',
        'email_subject' => 'x',
        'email_body' => 'x',
    ]);

    $company = Company::factory()->create([
        'name' => 'Acme BV',
        'contact_name' => null,
        'city' => null,
        'industry' => null,
    ]);

    $this->post(route('letters.store', $company));

    expect(Letter::sole()->body)
        ->toBe('Geachte heer, mevrouw | Acme BV viel mij op.');
});

it('leaves an unknown placeholder untouched so a typo is visible', function () {
    $this->actingAs(User::factory()->create());

    LetterTemplate::current()->update([
        'subject' => 'x',
        'body' => 'Hallo {{ compnay }}',
        'email_subject' => 'x',
        'email_body' => 'x',
    ]);

    $company = Company::factory()->create(['name' => 'Acme BV']);

    $this->post(route('letters.store', $company));

    expect(Letter::sole()->body)->toBe('Hallo {{ compnay }}');
});

it('ships the previous hardcoded copy as the seeded default', function () {
    expect(LetterTemplate::current()->body)
        ->toContain('Mijn naam is Robbin Thijssen, freelance softwareontwikkelaar bij Thijssen Software.')
        ->toContain('{{ greeting }}');
});
