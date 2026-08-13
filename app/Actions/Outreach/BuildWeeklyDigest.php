<?php

namespace App\Actions\Outreach;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\InboundMessage;
use App\Models\Letter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * The week's outreach in figures. Built separately from the mail so the numbers
 * can be asserted without rendering anything.
 *
 * @phpstan-type Digest array{since: string, sent: int, replies: int, bounces: int, overdue: int, dueSoon: int, missingEmail: int, quiet: bool}
 */
class BuildWeeklyDigest
{
    /**
     * @return Digest
     */
    public function handle(?CarbonInterface $since = null): array
    {
        $since ??= now()->subWeek();

        $sent = Letter::query()->where('sent_at', '>=', $since)->count();
        $replies = InboundMessage::query()->where('received_at', '>=', $since)->where('kind', 'reply')->count();
        $bounces = InboundMessage::query()->where('received_at', '>=', $since)->where('kind', 'bounce')->count();

        $overdue = $this->followUps()->whereDate('follow_up_at', '<', today())->count();
        $dueSoon = $this->followUps()->whereBetween('follow_up_at', [today(), today()->addWeek()])->count();

        return [
            'since' => $since->toDateString(),
            'sent' => $sent,
            'replies' => $replies,
            'bounces' => $bounces,
            'overdue' => $overdue,
            'dueSoon' => $dueSoon,
            'missingEmail' => Company::query()->whereNull('email')->count(),
            // Stated plainly rather than left to be inferred from four zeros:
            // a digest that only reports activity is silent in exactly the
            // weeks the reminder is most useful.
            'quiet' => $sent === 0 && $replies === 0 && $bounces === 0,
        ];
    }

    /**
     * @return Builder<Company>
     */
    private function followUps(): Builder
    {
        return Company::query()
            ->whereNotNull('follow_up_at')
            ->where('status', '!=', CompanyStatus::Closed)
            ->where('do_not_contact', false);
    }
}
