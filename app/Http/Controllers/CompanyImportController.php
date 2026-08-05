<?php

namespace App\Http\Controllers;

use App\Actions\Companies\ParseCompanyCsv;
use App\Enums\CompanyStatus;
use App\Http\Requests\PreviewCompanyImportRequest;
use App\Http\Requests\StoreCompanyImportRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanyImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('companies/Import', [
            'csv' => null,
            'preview' => null,
        ]);
    }

    public function preview(PreviewCompanyImportRequest $request, ParseCompanyCsv $parse): Response
    {
        $csv = $request->string('csv')->toString();

        return Inertia::render('companies/Import', [
            'csv' => $csv,
            'preview' => $parse->handle($csv),
        ]);
    }

    /**
     * Re-parses the same CSV rather than trusting parsed rows sent back by the
     * browser, so what gets written is what the server read.
     */
    public function store(StoreCompanyImportRequest $request, ParseCompanyCsv $parse): RedirectResponse
    {
        $rows = $parse->handle($request->string('csv')->toString())['rows'];

        /** @var list<int> $selected */
        $selected = $request->validated('lines');
        $selected = array_flip($selected);

        $created = 0;

        foreach ($rows as $row) {
            if ($row['errors'] !== [] || ! isset($selected[$row['line']])) {
                continue;
            }

            Company::create([...$row['values'], 'status' => CompanyStatus::New]);
            $created++;
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count company imported.|:count companies imported.', $created, ['count' => $created]),
        ]);

        return to_route('companies.index');
    }
}
