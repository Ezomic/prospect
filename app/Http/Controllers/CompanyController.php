<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('companies/Index', [
            'companies' => Company::query()
                ->orderBy('name')
                ->get(['id', 'name', 'website', 'email', 'contact_name', 'city', 'kvk_number', 'industry', 'status']),
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
