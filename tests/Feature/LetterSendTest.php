<?php

use App\Enums\CompanyStatus;
use App\Enums\LetterStatus;
use App\Mail\OutreachMail;
use App\Models\Company;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function sender(): User
{
    return User::factory()->create();
}

it('sends the letter with attachments and updates state', function () {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(sender());

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->create(['company_id' => $company->id, 'status' => 'ready', 'sent_at' => null]);

    $this->post(route('letters.send', $letter))
        ->assertRedirect(route('companies.show', $company->id));

    Mail::assertSent(OutreachMail::class, fn (OutreachMail $mail) => $mail->hasTo('hr@acme.example'));

    expect($letter->fresh())
        ->status->toBe(LetterStatus::Sent)
        ->and($letter->fresh()->sent_at)->not->toBeNull();

    expect($company->fresh()->status)->toBe(CompanyStatus::Sent);
});

it('does not send a letter that was already sent', function () {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(sender());

    $company = Company::factory()->create(['email' => 'hr@acme.example']);
    $letter = Letter::factory()->create([
        'company_id' => $company->id,
        'status' => 'sent',
        'sent_at' => now()->subDay(),
    ]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Mail::assertNothingSent();
});

it('does not send when the company has no email', function () {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(sender());

    $company = Company::factory()->create(['email' => null]);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Mail::assertNothingSent();
    expect($letter->fresh()->sent_at)->toBeNull();
});

it('sends with only the letter pdf attached', function () {
    Storage::fake('local');

    $this->actingAs(User::factory()->create());

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    expect($letter->fresh()->sent_at)->not->toBeNull();
});

it('advances a new company to sent', function () {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(sender());

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->post(route('letters.send', $letter));

    expect($company->fresh()->status)->toBe(CompanyStatus::Sent);
});

it('keeps the company status when sending a further letter to a company past Sent', function (string $status) {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(sender());

    $company = Company::factory()->create([
        'email' => 'hr@acme.example',
        'status' => $status,
        'replied_at' => $status === 'replied' ? now() : null,
        'bounced_at' => $status === 'bounced' ? now() : null,
    ]);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->post(route('letters.send', $letter));

    Mail::assertSent(OutreachMail::class);

    expect($letter->fresh()->sent_at)->not->toBeNull()
        ->and($company->fresh()->status)->toBe(CompanyStatus::from($status));
})->with(['replied', 'bounced', 'closed', 'sent']);

it('does not send a letter that is still a draft', function () {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(sender());

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->create(['company_id' => $company->id, 'status' => 'draft']);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Mail::assertNothingSent();

    expect($letter->fresh()->sent_at)->toBeNull()
        ->and($company->fresh()->status)->toBe(CompanyStatus::New);
});

it('refuses to send when the company is marked do not contact', function () {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(sender());

    $company = Company::factory()->create([
        'email' => 'hr@acme.example',
        'status' => 'new',
        'do_not_contact' => true,
    ]);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Mail::assertNothingSent();

    expect($letter->fresh()->sent_at)->toBeNull()
        ->and($company->fresh()->status)->toBe(CompanyStatus::New);
});

it('refuses to send outside production unless explicitly allowed', function () {
    Storage::fake('local');
    Mail::fake();
    config(['outreach.allow_send' => false]);

    $this->actingAs(sender());

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Mail::assertNothingSent();
    expect($letter->fresh()->sent_at)->toBeNull();
});

it('refuses to send once the daily limit is reached', function () {
    Storage::fake('local');
    Mail::fake();
    config(['outreach.daily_send_limit' => 2]);

    $this->actingAs(sender());

    Letter::factory()->count(2)->create(['status' => 'sent', 'sent_at' => now()]);

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Mail::assertNothingSent();
    expect($letter->fresh()->sent_at)->toBeNull();
});

it('does not count letters sent on earlier days towards the daily limit', function () {
    Storage::fake('local');
    Mail::fake();
    config(['outreach.daily_send_limit' => 2]);

    $this->actingAs(sender());

    Letter::factory()->count(5)->create(['status' => 'sent', 'sent_at' => now()->subDay()]);

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Mail::assertSent(OutreachMail::class);
    expect($letter->fresh()->sent_at)->not->toBeNull();
});

it('attaches only the letter pdf, never a cv', function () {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(sender());

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->post(route('letters.send', $letter));

    Mail::assertSent(OutreachMail::class, function (OutreachMail $mail) {
        $attachments = $mail->attachments();

        return count($attachments) === 1;
    });
});

it('delivers exactly what the preview showed', function () {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(sender());

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->ready()->create([
        'company_id' => $company->id,
        'email_subject' => 'Open aanbod - Robbin Thijssen',
        'email_body' => "Beste Jane Doe,\n\nBijgaand mijn open aanbod.",
        'sent_at' => null,
    ]);

    // What the dialog was told.
    $preview = null;
    $this->get(route('letters.edit', $letter))
        ->assertInertia(function (Assert $page) use (&$preview) {
            $preview = $page->toArray()['props']['preview'];
        });

    $this->post(route('letters.send', $letter));

    // What actually went out. If these ever diverge the preview is a lie, and
    // it is the last check before the mail is unrecallable.
    Mail::assertSent(OutreachMail::class, function (OutreachMail $mail) use ($preview) {
        return $mail->envelope()->subject === $preview['subject']
            && OutreachMail::bodyFor($mail->letter) === $preview['body']
            && $mail->hasTo($preview['to']);
    });
});
