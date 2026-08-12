<?php

namespace App\Http\Controllers;

use App\Actions\Letters\GenerateLetter;
use App\Enums\LetterStatus;
use App\Http\Requests\UpdateLetterRequest;
use App\Jobs\SendLetter;
use App\Mail\OutreachMail;
use App\Models\Company;
use App\Models\Letter;
use App\Services\Mail\LetterSender;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class LetterController extends Controller
{
    public function store(Company $company, GenerateLetter $generateLetter): RedirectResponse
    {
        $letter = $generateLetter->handle($company);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Letter generated.')]);

        return to_route('letters.edit', $letter);
    }

    public function edit(Letter $letter): Response
    {
        $letter->load('company');

        return Inertia::render('letters/Edit', [
            'letter' => $letter,
            'statuses' => $this->statusOptions(),
            'duplicateCompanies' => $this->companiesSharingEmail($letter),
            'releasable' => $this->isStuck($letter),
            'preview' => $this->preview($letter),
        ]);
    }

    public function update(UpdateLetterRequest $request, Letter $letter): RedirectResponse
    {
        if ($letter->sent_at !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('A sent letter can no longer be edited.')]);

            return back();
        }

        if ($letter->status === LetterStatus::Sending) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This letter is being sent and cannot be edited.')]);

            return back();
        }

        $letter->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Letter saved.')]);

        return to_route('companies.show', $letter->company_id);
    }

    public function send(Letter $letter, LetterSender $sender): RedirectResponse
    {
        $letter->load('company');

        // Guarded here rather than only in the job so a refusal is still an
        // immediate, visible answer instead of a silent failure on the queue.
        try {
            $sender->guard($letter);
        } catch (RuntimeException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        $letter->forceFill([
            'status' => LetterStatus::Sending,
            'queued_at' => now(),
            'send_error' => null,
        ])->save();

        SendLetter::dispatch($letter);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Letter queued for sending.')]);

        return to_route('companies.show', $letter->company_id);
    }

    public function pdf(Letter $letter): HttpResponse
    {
        $letter->load('company');

        $pdf = Pdf::loadView('pdf.letter', ['letter' => $letter]);

        return $pdf->stream("letter-{$letter->id}.pdf");
    }

    public function destroy(Letter $letter): RedirectResponse
    {
        $companyId = $letter->company_id;

        $letter->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Letter deleted.')]);

        return to_route('companies.show', $companyId);
    }

    /**
     * The message exactly as it will be delivered. Read through OutreachMail so
     * the preview cannot describe one thing while another is sent: this is the
     * last point at which a wrong greeting or an unresolved placeholder can be
     * caught, and after it the mail cannot be recalled.
     *
     * @return array{from: string, to: string|null, subject: string, body: string, attachments: array<int, string>}
     */
    private function preview(Letter $letter): array
    {
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        return [
            'from' => trim(
                (is_string($fromName) ? $fromName.' ' : '')
                .'<'.(is_string($fromAddress) ? $fromAddress : 'unknown').'>'
            ),
            'to' => $letter->company->email,
            'subject' => OutreachMail::subjectFor($letter),
            'body' => OutreachMail::bodyFor($letter),
            'attachments' => [OutreachMail::attachmentNameFor($letter)],
        ];
    }

    /**
     * Frees a letter that has sat in Sending long enough that no job can still
     * be working on it. A worker killed mid-send leaves no failed job behind,
     * so SendLetter::failed() never runs and nothing else would release it.
     */
    public function release(Letter $letter): RedirectResponse
    {
        if (! $this->isStuck($letter)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This letter is not stuck.')]);

            return back();
        }

        $letter->forceFill([
            'status' => LetterStatus::Ready,
            'queued_at' => null,
            'send_error' => __('The send never completed and was released by hand.'),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Letter released back to ready.')]);

        return back();
    }

    private function isStuck(Letter $letter): bool
    {
        if ($letter->status !== LetterStatus::Sending || $letter->sent_at !== null) {
            return false;
        }

        $minutes = config('outreach.stuck_after_minutes');
        $minutes = is_int($minutes) && $minutes > 0 ? $minutes : 5;

        return $letter->queued_at === null
            || $letter->queued_at->addMinutes($minutes)->isPast();
    }

    /**
     * Other companies on the same address, so the send confirmation can warn
     * that this recipient may already have heard from us under another name.
     *
     * @return array<int, string>
     */
    private function companiesSharingEmail(Letter $letter): array
    {
        if ($letter->company->email === null) {
            return [];
        }

        return Company::query()
            ->where('email', $letter->company->email)
            ->whereKeyNot($letter->company_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $company) => $company->name)
            ->all();
    }

    /**
     * The editable statuses only: Sent is reached by actually sending.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (LetterStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            [LetterStatus::Draft, LetterStatus::Ready],
        );
    }
}
