<?php

namespace App\Actions\Companies;

use App\Actions\Letters\GenerateLetter;
use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Support\Collection;

/**
 * Applies one action to the companies the user actually selected.
 *
 * Nothing here sends. Bulk generate produces drafts, because sending stays a
 * deliberate per-letter act with its own confirmation and preview: a bulk
 * button that puts mail in twenty inboxes is the one mistake this app cannot
 * take back.
 */
class ApplyBulkAction
{
    public function __construct(private readonly GenerateLetter $generateLetter) {}

    /**
     * @param  list<int>  $ids
     * @return array{applied: int, skipped: int}
     */
    public function handle(array $ids, string $action, ?CompanyStatus $status, ?string $reason): array
    {
        $companies = Company::query()->whereKey($ids)->get();

        return match ($action) {
            'status' => $this->setStatus($companies, $status),
            'do_not_contact' => $this->doNotContact($companies, $reason),
            'clear_follow_up' => $this->clearFollowUp($companies),
            'generate_letter' => $this->generateLetters($companies),
            default => ['applied' => 0, 'skipped' => $companies->count()],
        };
    }

    /**
     * @param  Collection<int, Company>  $companies
     * @return array{applied: int, skipped: int}
     */
    private function setStatus(Collection $companies, ?CompanyStatus $status): array
    {
        if ($status === null) {
            return ['applied' => 0, 'skipped' => $companies->count()];
        }

        foreach ($companies as $company) {
            $attributes = ['status' => $status];

            if ($status === CompanyStatus::Replied && $company->replied_at === null) {
                $attributes['replied_at'] = now();
            }

            if ($status === CompanyStatus::Bounced && $company->bounced_at === null) {
                $attributes['bounced_at'] = now();
            }

            $company->forceFill($attributes)->save();
        }

        return ['applied' => $companies->count(), 'skipped' => 0];
    }

    /**
     * Mirrors the single-company action exactly, so the flag, timestamp and
     * reason cannot drift apart depending on which route set them.
     *
     * @param  Collection<int, Company>  $companies
     * @return array{applied: int, skipped: int}
     */
    private function doNotContact(Collection $companies, ?string $reason): array
    {
        foreach ($companies as $company) {
            $company->forceFill([
                'do_not_contact' => true,
                'do_not_contact_at' => now(),
                'do_not_contact_reason' => $reason,
                'follow_up_at' => null,
            ])->save();
        }

        return ['applied' => $companies->count(), 'skipped' => 0];
    }

    /**
     * @param  Collection<int, Company>  $companies
     * @return array{applied: int, skipped: int}
     */
    private function clearFollowUp(Collection $companies): array
    {
        $applied = 0;

        foreach ($companies as $company) {
            if ($company->follow_up_at === null) {
                continue;
            }

            $company->forceFill(['follow_up_at' => null])->save();
            $applied++;
        }

        return ['applied' => $applied, 'skipped' => $companies->count() - $applied];
    }

    /**
     * @param  Collection<int, Company>  $companies
     * @return array{applied: int, skipped: int}
     */
    private function generateLetters(Collection $companies): array
    {
        $applied = 0;

        foreach ($companies as $company) {
            // Drafting a letter for a company that has asked not to be
            // contacted is pointless work that only makes an accident easier.
            if ($company->do_not_contact) {
                continue;
            }

            $this->generateLetter->handle($company);
            $applied++;
        }

        return ['applied' => $applied, 'skipped' => $companies->count() - $applied];
    }
}
