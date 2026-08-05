<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FollowUpController extends Controller
{
    public function index(): Response
    {
        $followUps = Company::query()
            ->select(['id', 'name', 'email', 'contact_name', 'status', 'follow_up_at', 'do_not_contact'])
            ->withMax('letters as last_contact_at', 'sent_at')
            ->whereNotNull('follow_up_at')
            ->where('status', '!=', CompanyStatus::Closed)
            ->orderBy('follow_up_at')
            ->orderBy('id')
            ->paginate(25)
            ->through(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'contact_name' => $company->contact_name,
                'status' => $company->status,
                'do_not_contact' => $company->do_not_contact,
                'follow_up_at' => $company->follow_up_at?->toDateString(),
                'last_contact_at' => $company->getAttribute('last_contact_at'),
                'group' => $this->group($company->follow_up_at),
            ]);

        return Inertia::render('follow-ups/Index', [
            'followUps' => $followUps,
        ]);
    }

    public function snooze(Company $company): RedirectResponse
    {
        // Snoozing an overdue reminder from its original date would leave it
        // overdue, so a past date restarts from today.
        $from = $company->follow_up_at !== null && $company->follow_up_at->greaterThan(today())
            ? $company->follow_up_at
            : today();

        $company->forceFill(['follow_up_at' => $from->addWeek()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Follow-up snoozed by a week.')]);

        return back();
    }

    private function group(?CarbonInterface $followUpAt): string
    {
        if ($followUpAt === null) {
            return 'upcoming';
        }

        if ($followUpAt->lessThan(today())) {
            return 'overdue';
        }

        return $followUpAt->isSameDay(today()) ? 'today' : 'upcoming';
    }
}
