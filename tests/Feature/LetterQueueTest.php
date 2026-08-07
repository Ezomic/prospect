<?php

use App\Enums\LetterStatus;
use App\Jobs\AppendLetterToSentFolder;
use App\Jobs\SendLetter;
use App\Models\Company;
use App\Models\Letter;
use App\Models\User;
use App\Services\Mail\LetterSender;
use App\Services\Mail\SentFolderAppender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function senderUser(): User
{
    return User::factory()->create();
}

it('queues the send instead of delivering in the request', function () {
    Storage::fake('local');
    Queue::fake();

    $this->actingAs(senderUser());

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->post(route('letters.send', $letter))
        ->assertRedirect(route('companies.show', $company->id));

    Queue::assertPushed(SendLetter::class, fn (SendLetter $job) => $job->letter->is($letter));

    expect($letter->fresh())
        ->status->toBe(LetterStatus::Sending)
        ->sent_at->toBeNull();
});

it('does not queue anything when a guardrail refuses the send', function () {
    Storage::fake('local');
    Queue::fake();

    $this->actingAs(senderUser());

    $company = Company::factory()->create([
        'email' => 'hr@acme.example',
        'do_not_contact' => true,
    ]);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);

    $this->post(route('letters.send', $letter))->assertRedirect();

    Queue::assertNothingPushed();

    expect($letter->fresh()->status)->toBe(LetterStatus::Ready);
});

// No Mail::fake here: the raw MIME the append job files is captured from the
// Symfony message, which only exists when a real transport builds it. The array
// mailer does that without sending anything.
it('queues the sent-folder append rather than appending inline', function () {
    Storage::fake('local');
    Queue::fake([AppendLetterToSentFolder::class]);

    senderUser();

    $company = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'new']);
    $letter = Letter::factory()->ready()->create(['company_id' => $company->id, 'sent_at' => null]);
    $letter->load('company');

    app(LetterSender::class)->deliver($letter);

    Queue::assertPushed(AppendLetterToSentFolder::class);

    expect($letter->fresh()->status)->toBe(LetterStatus::Sent);
});

it('hands a failed letter back with the reason instead of leaving it sending', function () {
    Storage::fake('local');

    senderUser();
    $letter = Letter::factory()->create(['status' => 'sending', 'sent_at' => null]);

    (new SendLetter($letter))->failed(new RuntimeException('SMTP host unreachable'));

    expect($letter->fresh())
        ->status->toBe(LetterStatus::Ready)
        ->send_error->toBe('SMTP host unreachable');
});

it('leaves an already delivered letter alone when the failure handler runs', function () {
    Storage::fake('local');

    senderUser();
    $letter = Letter::factory()->create(['status' => 'sent', 'sent_at' => now()]);

    (new SendLetter($letter))->failed(new RuntimeException('too late'));

    expect($letter->fresh())
        ->status->toBe(LetterStatus::Sent)
        ->send_error->toBeNull();
});

it('deletes the stashed message when imap is not configured', function () {
    Storage::fake('local');
    config(['services.outreach_imap.host' => null]);

    Storage::disk('local')->put('outreach-sent/1-abc.eml', 'RAW');

    (new AppendLetterToSentFolder('outreach-sent/1-abc.eml'))->handle(app(SentFolderAppender::class));

    Storage::disk('local')->assertMissing('outreach-sent/1-abc.eml');
});
