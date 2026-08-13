<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 * Folds one company into another and deletes the duplicate.
 *
 * Two rows for the same firm mean two pitches to the same inbox. The import
 * avoids creating them and the send warns about them, but nothing resolved the
 * ones already there.
 */
class MergeCompanies
{
    /**
     * The columns a merge can take from either side.
     *
     * @var list<string>
     */
    public const FIELDS = [
        'name', 'website', 'email', 'contact_name', 'contact_role', 'city',
        'kvk_number', 'industry', 'source', 'linkedin_url', 'lead_score',
        'first_contact_channel', 'notes',
    ];

    /**
     * @param  list<string>  $takeFromDuplicate  columns whose value should come from the duplicate
     */
    public function handle(Company $survivor, Company $duplicate, array $takeFromDuplicate = []): Company
    {
        return DB::transaction(function () use ($survivor, $duplicate, $takeFromDuplicate) {
            $duplicate->letters()->update(['company_id' => $survivor->id]);
            $duplicate->interactions()->update(['company_id' => $survivor->id]);
            $duplicate->inboundMessages()->update(['company_id' => $survivor->id]);

            $survivor->forceFill([
                ...$this->fields($survivor, $duplicate, $takeFromDuplicate),
                ...$this->pipeline($survivor, $duplicate),
            ])->save();

            $duplicate->delete();

            return $survivor->refresh();
        });
    }

    /**
     * The chosen value wins, and a blank on the survivor is always filled from
     * the duplicate: there is no reason for a merge to lose data neither side
     * disagreed about.
     *
     * @param  list<string>  $takeFromDuplicate
     * @return array<string, mixed>
     */
    private function fields(Company $survivor, Company $duplicate, array $takeFromDuplicate): array
    {
        $values = [];

        foreach (self::FIELDS as $field) {
            if (in_array($field, $takeFromDuplicate, true)) {
                $values[$field] = $duplicate->getAttribute($field);

                continue;
            }

            if ($survivor->getAttribute($field) === null) {
                $values[$field] = $duplicate->getAttribute($field);
            }
        }

        return $values;
    }

    /**
     * Pipeline state is not a preference, so it is never chosen by hand: the
     * further-along status survives, contact stamps keep the earliest moment
     * either row recorded, and do-not-contact is sticky. Merging a flagged
     * company into an unflagged one must not quietly permit contact again.
     *
     * @return array<string, mixed>
     */
    private function pipeline(Company $survivor, Company $duplicate): array
    {
        $flagged = $survivor->do_not_contact || $duplicate->do_not_contact;

        return [
            'status' => $duplicate->status->rank() > $survivor->status->rank()
                ? $duplicate->status
                : $survivor->status,
            'replied_at' => $this->earliest($survivor->replied_at, $duplicate->replied_at),
            'bounced_at' => $this->earliest($survivor->bounced_at, $duplicate->bounced_at),
            'follow_up_at' => $flagged
                ? null
                : $this->earliest($survivor->follow_up_at, $duplicate->follow_up_at),
            'do_not_contact' => $flagged,
            'do_not_contact_at' => $flagged
                ? $this->earliest($survivor->do_not_contact_at, $duplicate->do_not_contact_at) ?? now()
                : null,
            'do_not_contact_reason' => $flagged
                ? ($survivor->do_not_contact_reason ?? $duplicate->do_not_contact_reason)
                : null,
        ];
    }

    private function earliest(mixed $a, mixed $b): mixed
    {
        if ($a === null) {
            return $b;
        }

        if ($b === null) {
            return $a;
        }

        return $a <= $b ? $a : $b;
    }
}
