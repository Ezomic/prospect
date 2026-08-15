<?php

use App\Enums\LetterLanguage;
use App\Enums\LetterType;
use App\Models\Company;
use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('ships a template for every language and type', function () {
    foreach (LetterLanguage::cases() as $language) {
        foreach (LetterType::cases() as $type) {
            $template = LetterTemplate::current($type, $language);

            expect($template->subject)->not->toBeEmpty()
                ->and($template->body)->not->toBeEmpty();
        }
    }

    expect(LetterTemplate::count())->toBe(6);
});

it('writes to a German company in German', function () {
    $company = Company::factory()->create([
        'name' => 'Mintellity GmbH',
        'contact_name' => 'Jonas Weber',
        'city' => 'Münster',
        'industry' => 'Webentwicklung',
        'language' => LetterLanguage::German,
    ]);

    $this->post(route('letters.store', $company))->assertRedirect();

    $letter = $company->letters()->sole();

    expect($letter->language)->toBe(LetterLanguage::German)
        ->and($letter->body)->toContain('Guten Tag Jonas Weber')
        ->and($letter->body)->toContain('Mintellity GmbH in Münster ist mir aufgefallen')
        ->and($letter->body)->toContain('im Bereich Webentwicklung.')
        ->and($letter->body)->toContain('Mit freundlichen Grüßen')
        // Never the Dutch copy.
        ->and($letter->body)->not->toContain('Met vriendelijke groet');
});

it('writes to an English company in English', function () {
    $company = Company::factory()->create([
        'name' => 'Acme Ltd',
        'contact_name' => null,
        'city' => 'Leeds',
        'industry' => null,
        'language' => LetterLanguage::English,
    ]);

    $this->post(route('letters.store', $company));

    expect($company->letters()->sole()->body)
        ->toContain('Dear Sir or Madam')
        ->toContain('Acme Ltd in Leeds caught my eye.')
        ->toContain('Kind regards');
});

it('keeps Dutch as the default for a company with no language set', function () {
    $company = Company::factory()->create(['contact_name' => 'Jan Jansen']);

    $this->post(route('letters.store', $company));

    expect($company->fresh()->language)->toBe(LetterLanguage::Dutch)
        ->and($company->letters()->sole()->body)->toContain('Beste Jan Jansen');
});

it('avoids the German word for a speculative job application', function () {
    // Same reasoning as PROS-38: this is a supplier pitch, not a jobseeker.
    $german = LetterTemplate::current(LetterType::OpenAanbod, LetterLanguage::German);

    expect(strtolower($german->subject.$german->body))
        ->not->toContain('initiativbewerbung')
        ->not->toContain('bewerbung');
});

it('dates a German follow-up in German', function () {
    $company = Company::factory()->create([
        'name' => 'Nova Fortis',
        'language' => LetterLanguage::German,
    ]);

    Letter::factory()->create([
        'company_id' => $company->id,
        'sent_at' => Date::parse('2026-07-27 10:00:00'),
    ]);

    $this->post(route('letters.store', $company), ['type' => 'follow_up']);

    $followUp = $company->letters()->where('type', LetterType::FollowUp)->sole();

    // "27 juli 2026" in a German letter reads as a mail merge gone wrong.
    expect($followUp->body)->toContain('27. Juli 2026')
        ->and($followUp->body)->not->toContain('juli');
});

it('edits one language without touching another', function () {
    $this->patch(route('letter-template.update'), [
        'type' => 'open_aanbod',
        'language' => 'de',
        'subject' => 'Neuer Betreff für {{ company }}',
        'body' => 'Neuer Text.',
        'email_subject' => 'Neuer Betreff',
        'email_body' => 'Neue Mail.',
    ])->assertRedirect();

    expect(LetterTemplate::current(LetterType::OpenAanbod, LetterLanguage::German)->subject)
        ->toBe('Neuer Betreff für {{ company }}')
        ->and(LetterTemplate::current(LetterType::OpenAanbod, LetterLanguage::Dutch)->subject)
        ->toContain('Open aanbod');
});

it('offers every language on the settings page', function () {
    $this->get(route('letter-template.edit', ['language' => 'de']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('language', 'de')
            ->has('languages', 3)
            ->where('template.subject', fn ($subject) => str_contains((string) $subject, 'Freiberufliche'))
        );
});

it('rejects a language it does not know', function () {
    $company = Company::factory()->create();

    $this->put(route('companies.update', $company), [
        'name' => $company->name,
        'status' => 'new',
        'language' => 'fr',
    ])->assertSessionHasErrors('language');
});

it('renders the sample preview in the chosen language', function () {
    $this->get(route('letter-template.edit', ['language' => 'en']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sample.greeting', 'Dear Jane Doe')
        );
});
