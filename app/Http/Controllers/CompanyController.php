<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Http\Requests\IndexCompanyRequest;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(IndexCompanyRequest $request): Response
    {
        $search = $request->validated('search');
        $status = $request->validated('status');

        $companies = Company::query()
            ->when($search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('contact_name', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhere('industry', 'like', "%{$search}%")
            ))
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->get(['id', 'name', 'website', 'email', 'contact_name', 'city', 'kvk_number', 'industry', 'status']);

        return Inertia::render('companies/Index', [
            'companies' => $companies,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function show(Company $company): Response
    {
        return Inertia::render('companies/Show', [
            'company' => $company,
            'letters' => $company->letters()->get(['id', 'company_id', 'subject', 'status', 'generated_at', 'sent_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('companies/Create', [
            'statuses' => $this->statusOptions(),
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
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company updated.')]);

        return to_route('companies.index');
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
    private function statusOptions(): array
    {
        return array_map(
            fn (CompanyStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            CompanyStatus::cases(),
        );
    }
}
