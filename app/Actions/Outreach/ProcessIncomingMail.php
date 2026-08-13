<?php

namespace App\Actions\Outreach;

use App\Enums\CompanyStatus;
use App\Enums\InboundMessageKind;
use App\Models\Company;
use App\Services\Mail\IncomingMessage;

/**
 * Applies an inbound message to the pipeline: a reply from a company we emailed
 * marks it Replied; a bounce for a company we emailed marks it Bounced; an
 * automatic answer such as an out-of-office notice marks nothing. Only
 * companies still in Sent are transitioned, so manual outcomes are never
 * overwritten and reprocessing the same message is a no-op.
 */
class ProcessIncomingMail
{
    /**
     * Returns whether the message was acted on, which decides whether it is
     * marked read. An auto-reply counts as not acted on deliberately: it is a
     * real message a human may want to see, and it changes nothing here.
     */
    public function handle(IncomingMessage $message): bool
    {
        if ($message->isBounce) {
            $acted = false;

            foreach ($message->failedRecipients as $email) {
                $acted = $this->transition($email, CompanyStatus::Bounced, 'bounced_at', $message) || $acted;
            }

            return $acted;
        }

        // An out-of-office notice is not a company answering. Checked after the
        // bounce branch because delivery reports are automatic messages too.
        if ($message->isAutoReply) {
            return false;
        }

        return $this->transition($message->from, CompanyStatus::Replied, 'replied_at', $message);
    }

    private function transition(
        string $email,
        CompanyStatus $status,
        string $stampColumn,
        IncomingMessage $message,
    ): bool {
        $company = Company::query()
            ->where('email', $email)
            ->where('status', CompanyStatus::Sent)
            ->first();

        if ($company === null) {
            return false;
        }

        $company->forceFill([
            'status' => $status,
            $stampColumn => $company->{$stampColumn} ?? now(),
            // A company that answered or bounced does not need chasing. Sends
            // schedule a reminder automatically, so leaving it would turn a
            // reply into a prompt to write again.
            'follow_up_at' => null,
        ])->save();

        $this->record($company, $message, $status);

        return true;
    }

    /**
     * Keeps the message that caused the transition, so the reply can be read
     * where the decision about what to do next is made rather than only in the
     * mailbox.
     *
     * Deduplicated on message id because a message the poller leaves unread is
     * seen again on the next run: the transitions are idempotent, the storage
     * would not be.
     */
    private function record(Company $company, IncomingMessage $message, CompanyStatus $status): void
    {
        $attributes = [
            'kind' => $status === CompanyStatus::Bounced
                ? InboundMessageKind::Bounce
                : InboundMessageKind::Reply,
            'from' => $message->from,
            'subject' => $message->subject !== '' ? $message->subject : null,
            'body' => $message->body !== '' ? $message->body : null,
            'received_at' => $message->receivedAt ?? now(),
        ];

        if ($message->messageId === null || $message->messageId === '') {
            $company->inboundMessages()->create($attributes);

            return;
        }

        $company->inboundMessages()->firstOrCreate(
            ['message_id' => $message->messageId],
            $attributes,
        );
    }
}
