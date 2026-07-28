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

uses(RefreshDatabase::class);

function userWithCv(): User
{
    Storage::disk('local')->put('cv/test.pdf', 'PDF-BYTES');

    return User::factory()->create([
        'cv_path' => 'cv/test.pdf',
        'cv_original_name' => 'robbin-cv.pdf',
    ]);
}

it('sends the letter with attachments and updates state', function () {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(userWithCv());

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

    $this->actingAs(userWithCv());

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

    $this->actingAs(userWithCv());

    $company = Company::factory()->create(['email' => null]);
    $letter = Letter::factory()->create(['company_id' => $company->id]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Mail::assertNothingSent();
    expect($letter->fresh()->sent_at)->toBeNull();
});

it('does not send when the user has no cv', function () {
    Storage::fake('local');
    Mail::fake();

    $this->actingAs(User::factory()->create(['cv_path' => null]));

    $company = Company::factory()->create(['email' => 'hr@acme.example']);
    $letter = Letter::factory()->create(['company_id' => $company->id]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Mail::assertNothingSent();
    expect($letter->fresh()->sent_at)->toBeNull();
});
