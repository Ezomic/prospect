<?php

namespace App\Http\Controllers;

use App\Actions\Letters\GenerateLetter;
use App\Enums\LetterStatus;
use App\Http\Requests\UpdateLetterRequest;
use App\Models\Company;
use App\Models\Letter;
use App\Models\User;
use App\Services\Mail\LetterSender;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function update(UpdateLetterRequest $request, Letter $letter): RedirectResponse
    {
        $letter->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Letter saved.')]);

        return to_route('companies.show', $letter->company_id);
    }

    public function send(Request $request, Letter $letter, LetterSender $sender): RedirectResponse
    {
        $letter->load('company');

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        try {
            $sender->send($letter, $user);
        } catch (RuntimeException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Letter sent.')]);

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
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (LetterStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            LetterStatus::cases(),
        );
    }
}
