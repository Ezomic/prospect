<?php

namespace App\Http\Controllers;

use App\Actions\Companies\ApplyBulkAction;
use App\Actions\Companies\BuildCompanyTimeline;
use App\Actions\Companies\MergeCompanies;
use App\Actions\Companies\ParseCompanyCsv;
use App\Enums\CompanyStatus;
use App\Enums\InteractionKind;
use App\Enums\LetterLanguage;
use App\Http\Requests\BulkCompanyActionRequest;
use App\Http\Requests\IndexCompanyRequest;
use App\Http\Requests\MergeCompanyRequest;
use App\Http\Requests\ScheduleFollowUpRequest;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Requests\UpdateCompanyStatusRequest;
use App\Http\Requests\UpdateDoNotContactRequest;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
{
    public function index(IndexCompanyRequest $request): Response
    {
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;
        $sort = $request->sort();
        $direction = $request->direction();
        $missingEmail = $request->boolean('missing_email');

        $companies = $this->filtered($request)
            ->select(['id', 'name', 'website', 'email', 'contact_name', 'city', 'kvk_number', 'industry', 'status', 'source', 'lead_score', 'do_not_contact'])
            ->withMax('letters as last_contact_at', 'sent_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('companies/Index', [
            'companies' => $companies,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
                'missing_email' => $missingEmail,
            ],
            'missingEmailCount' => Company::query()->whereNull('email')->count(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Exports the filtered view rather than the whole table, so the
     * missing-email filter doubles as a worklist: pull those into a
     * spreadsheet, research the addresses, and bring them back through the
     * importer, which matches on email and kvk_number.
     */
    public function export(IndexCompanyRequest $request): StreamedResponse
    {
        $companies = $this->filtered($request)->get();
        $columns = ParseCompanyCsv::COLUMNS;

        return response()->streamDownload(function () use ($companies, $columns) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $columns);

            foreach ($companies as $company) {
                $row = [];

                foreach ($columns as $column) {
                    $value = $company->getAttribute($column);

                    $row[] = is_scalar($value) ? $value : null;
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'companies-'.today()->toDateString().'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return Builder<Company>
     */
    private function filtered(IndexCompanyRequest $request): Builder
    {
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;

        return Company::query()
            ->when($search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('contact_name', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhere('industry', 'like', "%{$search}%")
            ))
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->boolean('missing_email'), fn (Builder $query) => $query->whereNull('email'))
            ->orderBy(IndexCompanyRequest::SORTABLE[$request->sort()], $request->direction())
            // A stable tie-break, so paging through equal values cannot repeat
            // or skip a row.
            ->orderBy('id');
    }

    public function show(Company $company, BuildCompanyTimeline $timeline): Response
    {
        $company->load(['letters', 'inboundMessages', 'interactions']);

        return Inertia::render('companies/Show', [
            'company' => $company,
            'letters' => $company->letters()->get(['id', 'company_id', 'subject', 'status', 'generated_at', 'sent_at']),
            'timeline' => $timeline->handle($company),
            'interactionKinds' => array_map(
                fn (InteractionKind $kind) => ['value' => $kind->value, 'label' => $kind->label()],
                InteractionKind::cases(),
            ),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('companies/Create', [
            'statuses' => $this->statusOptions(),
            'languages' => $this->languageOptions(),
        ]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        Company::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company created.')]);

        return to_route('companies.index');
    }

    public function edit(Company $company): Response
    {
        return Inertia::render('companies/Edit', [
            'company' => $company,
            'statuses' => $this->statusOptions(),
            'languages' => $this->languageOptions(),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company updated.')]);

        return to_route('companies.index');
    }

    public function updateStatus(UpdateCompanyStatusRequest $request, Company $company): RedirectResponse
    {
        $status = CompanyStatus::from($request->string('status')->toString());

        $attributes = ['status' => $status];

        if ($status === CompanyStatus::Replied && $company->replied_at === null) {
            $attributes['replied_at'] = now();
        }

        if ($status === CompanyStatus::Bounced && $company->bounced_at === null) {
            $attributes['bounced_at'] = now();
        }

        $company->forceFill($attributes)->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Status updated.')]);

        return back();
    }

    public function followUp(ScheduleFollowUpRequest $request, Company $company): RedirectResponse
    {
        $company->forceFill(['follow_up_at' => $request->validated('follow_up_at')])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Follow-up updated.')]);

        return back();
    }

    /**
     * Terminal by design: a company that has asked not to be contacted drops
     * out of follow-ups as well as sends, so the reminder cannot quietly bring
     * it back around later.
     */
    public function doNotContact(UpdateDoNotContactRequest $request, Company $company): RedirectResponse
    {
        $flagged = $request->boolean('do_not_contact');

        $company->forceFill($flagged ? [
            'do_not_contact' => true,
            'do_not_contact_at' => now(),
            'do_not_contact_reason' => $request->string('reason')->toString() ?: null,
            'follow_up_at' => null,
        ] : [
            'do_not_contact' => false,
            'do_not_contact_at' => null,
            'do_not_contact_reason' => null,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $flagged
                ? __('Marked do not contact.')
                : __('Contact allowed again.'),
        ]);

        return back();
    }

    /**
     * Candidate duplicates for a company: anything sharing its address, KvK
     * number or name, which is the same signal the importer uses to flag a row
     * rather than create it.
     */
    public function merge(Company $company): Response
    {
        $candidates = Company::query()
            ->whereKeyNot($company->id)
            ->where(function (Builder $query) use ($company) {
                $query->where('name', $company->name);

                if ($company->email !== null) {
                    $query->orWhere('email', $company->email);
                }

                if ($company->kvk_number !== null) {
                    $query->orWhere('kvk_number', $company->kvk_number);
                }
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('companies/Merge', [
            'company' => $company,
            'candidates' => $candidates,
            'fields' => MergeCompanies::FIELDS,
        ]);
    }

    public function applyMerge(MergeCompanyRequest $request, Company $company, MergeCompanies $merge): RedirectResponse
    {
        $duplicate = Company::query()->findOrFail($request->integer('duplicate_id'));

        $merge->handle($company, $duplicate, $request->takeFromDuplicate());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name was merged in and removed.', ['name' => $duplicate->name]),
        ]);

        return to_route('companies.show', $company->id);
    }

    public function bulk(BulkCompanyActionRequest $request, ApplyBulkAction $apply): RedirectResponse
    {
        $status = $request->filled('status')
            ? CompanyStatus::from($request->string('status')->toString())
            : null;

        $result = $apply->handle(
            $request->ids(),
            $request->string('action')->toString(),
            $status,
            $request->string('reason')->toString() ?: null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['skipped'] > 0
                ? __(':applied updated, :skipped skipped.', $result)
                : trans_choice(':count company updated.|:count companies updated.', $result['applied'], ['count' => $result['applied']]),
        ]);

        return back();
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company deleted.')]);

        return to_route('companies.index');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function languageOptions(): array
    {
        return array_map(
            fn (LetterLanguage $language) => ['value' => $language->value, 'label' => $language->label()],
            LetterLanguage::cases(),
        );
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (CompanyStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            CompanyStatus::cases(),
        );
    }
}
