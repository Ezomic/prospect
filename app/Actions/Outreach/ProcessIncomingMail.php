<?php

namespace App\Actions\Outreach;

use App\Enums\CompanyStatus;
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
                $acted = $this->transition($email, CompanyStatus::Bounced, 'bounced_at') || $acted;
            }

            return $acted;
        }

        // An out-of-office notice is not a company answering. Checked after the
        // bounce branch because delivery reports are automatic messages too.
        if ($message->isAutoReply) {
            return false;
        }

        return $this->transition($message->from, CompanyStatus::Replied, 'replied_at');
    }

    private function transition(string $email, CompanyStatus $status, string $stampColumn): bool
    {
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
        ])->save();

        return true;
    }
}
