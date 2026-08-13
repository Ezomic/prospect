<?php

namespace App\Console\Commands;

use App\Actions\Outreach\BuildWeeklyDigest;
use App\Mail\WeeklyDigestMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Outreach fails quietly: nothing sent for three weeks looks exactly like a
 * normal Tuesday. This says so out loud once a week.
 */
class SendWeeklyDigest extends Command
{
    protected $signature = 'outreach:digest';

    protected $description = 'Email a weekly summary of the outreach pipeline';

    public function handle(BuildWeeklyDigest $build): int
    {
        $recipient = User::query()->orderBy('id')->first();

        if ($recipient === null) {
            $this->warn('No user to send the digest to; skipping.');

            return self::SUCCESS;
        }

        Mail::to($recipient->email)->send(new WeeklyDigestMail($build->handle()));

        $this->info("Weekly digest sent to {$recipient->email}.");

        return self::SUCCESS;
    }
}
