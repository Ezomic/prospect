<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Letters\GenerateLetter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateLetterTemplateRequest;
use App\Models\Company;
use App\Models\LetterTemplate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LetterTemplateController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/LetterTemplate', [
            'template' => LetterTemplate::current()->only(['subject', 'body', 'email_subject', 'email_body']),
            'defaults' => LetterTemplate::DEFAULTS,
            'sample' => GenerateLetter::placeholders($this->sampleCompany()),
        ]);
    }

    public function update(UpdateLetterTemplateRequest $request): RedirectResponse
    {
        LetterTemplate::current()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template saved.')]);

        return to_route('letter-template.edit');
    }

    /**
     * Never persisted: it exists only so the page can preview the placeholders
     * against something that looks like a real company.
     */
    private function sampleCompany(): Company
    {
        return new Company([
            'name' => 'Acme BV',
            'contact_name' => 'Jane Doe',
            'city' => 'Enschede',
            'industry' => 'softwareontwikkeling',
        ]);
    }
}
