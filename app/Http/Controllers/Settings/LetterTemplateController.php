<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Letters\GenerateLetter;
use App\Enums\LetterType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateLetterTemplateRequest;
use App\Models\Company;
use App\Models\LetterTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LetterTemplateController extends Controller
{
    public function edit(Request $request): Response
    {
        $type = LetterType::tryFrom($request->string('type')->toString()) ?? LetterType::OpenAanbod;

        return Inertia::render('settings/LetterTemplate', [
            'type' => $type->value,
            'types' => array_map(
                fn (LetterType $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'description' => $case->description(),
                ],
                LetterType::cases(),
            ),
            'template' => LetterTemplate::current($type)->only(['subject', 'body', 'email_subject', 'email_body']),
            'defaults' => LetterTemplate::defaultsFor($type),
            'sample' => GenerateLetter::placeholders($this->sampleCompany()),
        ]);
    }

    public function update(UpdateLetterTemplateRequest $request): RedirectResponse
    {
        $type = $request->letterType();

        LetterTemplate::current($type)->update([
            'subject' => $request->string('subject')->toString(),
            'body' => $request->string('body')->toString(),
            'email_subject' => $request->string('email_subject')->toString(),
            'email_body' => $request->string('email_body')->toString(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template saved.')]);

        return to_route('letter-template.edit', ['type' => $type->value]);
    }

    /**
     * Never persisted: it exists only so the page can preview the placeholders
     * against something that looks like a real company. previous_sent_at comes
     * out blank here, since an unsaved company has no letters, which is the
     * same thing a real follow-up shows before anything has been sent.
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
