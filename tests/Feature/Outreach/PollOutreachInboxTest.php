<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Services\Mail\Inbox;
use App\Services\Mail\IncomingMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('polls the inbox and applies outcomes to companies', function () {
    $replied = Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);
    $bounced = Company::factory()->create(['email' => 'jobs@globex.example', 'status' => 'sent']);

    $fake = new class implements Inbox
    {
        public function configured(): bool
        {
            return true;
        }

        public function eachUnseen(callable $handler): void
        {
            $handler(new IncomingMessage('hr@acme.example', 'Re: Open aanbod', false));
            $handler(new IncomingMessage('mailer-daemon@x', 'Undelivered', true, ['jobs@globex.example']));
        }
    };
    $this->app->instance(Inbox::class, $fake);

    $this->artisan('outreach:poll')->assertSuccessful();

    expect($replied->fresh()->status)->toBe(CompanyStatus::Replied)
        ->and($bounced->fresh()->status)->toBe(CompanyStatus::Bounced);
});

it('does nothing when imap is not configured', function () {
    $fake = new class implements Inbox
    {
        public bool $polled = false;

        public function configured(): bool
        {
            return false;
        }

        public function eachUnseen(callable $handler): void
        {
            $this->polled = true;
        }
    };
    $this->app->instance(Inbox::class, $fake);

    $this->artisan('outreach:poll')->assertSuccessful();

    expect($fake->polled)->toBeFalse();
});

it('marks only the messages it acted on as read', function () {
    Company::factory()->create(['email' => 'hr@acme.example', 'status' => 'sent']);

    $fake = new class implements Inbox
    {
        /** @var list<string> */
        public array $markedRead = [];

        public function configured(): bool
        {
            return true;
        }

        public function eachUnseen(callable $handler): void
        {
            $messages = [
                new IncomingMessage('hr@acme.example', 'Re: Open aanbod', false),
                new IncomingMessage('accountant@example.com', 'Factuur augustus', false),
            ];

            foreach ($messages as $message) {
                if ($handler($message) === true) {
                    $this->markedRead[] = $message->from;
                }
            }
        }
    };
    $this->app->instance(Inbox::class, $fake);

    $this->artisan('outreach:poll')->assertSuccessful();

    // The unrelated invoice stays unread and visible to a human.
    expect($fake->markedRead)->toBe(['hr@acme.example']);
});
