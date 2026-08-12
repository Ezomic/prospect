<?php

namespace App\Actions\Companies;

use App\Enums\InboundMessageKind;
use App\Models\Company;
use App\Models\InboundMessage;
use Carbon\CarbonInterface;

/**
 * Everything that has happened with a company, newest first: letters generated
 * and sent, replies and bounces, logged interactions, and the pipeline stamps.
 *
 * The company page could show each of these in its own card, and did, but that
 * cannot answer the question the CRM exists for, which is what happened and in
 * what order.
 *
 * @phpstan-type TimelineEntry array{at: string, kind: string, title: string, detail: string|null, letter_id: int|null, interaction_id: int|null}
 */
class BuildCompanyTimeline
{
    /**
     * @return list<TimelineEntry>
     */
    public function handle(Company $company): array
    {
        $entries = [
            ...$this->letters($company),
            ...$this->messages($company),
            ...$this->interactions($company),
            ...$this->stamps($company),
        ];

        usort($entries, fn (array $a, array $b) => strcmp($b['at'], $a['at']));

        return $entries;
    }

    /**
     * @return list<TimelineEntry>
     */
    private function letters(Company $company): array
    {
        $entries = [];

        foreach ($company->letters as $letter) {
            if ($letter->generated_at !== null) {
                $entries[] = $this->entry(
                    $letter->generated_at,
                    'letter_generated',
                    'Letter drafted',
                    $letter->subject,
                    letterId: $letter->id,
                );
            }

            if ($letter->sent_at !== null) {
                $entries[] = $this->entry(
                    $letter->sent_at,
                    'letter_sent',
                    'Letter sent',
                    $letter->subject,
                    letterId: $letter->id,
                );
            }
        }

        return $entries;
    }

    /**
     * @return list<TimelineEntry>
     */
    private function messages(Company $company): array
    {
        $entries = [];

        foreach ($company->inboundMessages as $message) {
            $entries[] = $this->entry(
                $message->received_at,
                $message->kind === InboundMessageKind::Bounce ? 'bounce' : 'reply',
                $message->kind->label().' from '.$message->from,
                trim(($message->subject ?? '')."\n\n".($message->body ?? '')) ?: null,
            );
        }

        return $entries;
    }

    /**
     * @return list<TimelineEntry>
     */
    private function interactions(Company $company): array
    {
        $entries = [];

        foreach ($company->interactions as $interaction) {
            $entries[] = $this->entry(
                $interaction->occurred_at,
                'interaction',
                $interaction->kind->label(),
                $interaction->summary,
                interactionId: $interaction->id,
            );
        }

        return $entries;
    }

    /**
     * The pipeline stamps, but only where no stored message already explains
     * them. A reply that arrived by mail is one event, not an entry for the
     * message and another for the timestamp it set.
     *
     * @return list<TimelineEntry>
     */
    private function stamps(Company $company): array
    {
        $entries = [];

        $kinds = $company->inboundMessages->map(fn (InboundMessage $message) => $message->kind);

        if ($company->replied_at !== null && ! $kinds->contains(InboundMessageKind::Reply)) {
            $entries[] = $this->entry($company->replied_at, 'reply', 'Marked replied', null);
        }

        if ($company->bounced_at !== null && ! $kinds->contains(InboundMessageKind::Bounce)) {
            $entries[] = $this->entry($company->bounced_at, 'bounce', 'Marked bounced', null);
        }

        if ($company->do_not_contact_at !== null) {
            $entries[] = $this->entry(
                $company->do_not_contact_at,
                'do_not_contact',
                'Marked do not contact',
                $company->do_not_contact_reason,
            );
        }

        if ($company->created_at !== null) {
            $entries[] = $this->entry($company->created_at, 'added', 'Added to the pipeline', null);
        }

        return $entries;
    }

    /**
     * @return TimelineEntry
     */
    private function entry(
        CarbonInterface $at,
        string $kind,
        string $title,
        ?string $detail,
        ?int $letterId = null,
        ?int $interactionId = null,
    ): array {
        return [
            'at' => $at->toIso8601String(),
            'kind' => $kind,
            'title' => $title,
            'detail' => $detail,
            'letter_id' => $letterId,
            'interaction_id' => $interactionId,
        ];
    }
}
