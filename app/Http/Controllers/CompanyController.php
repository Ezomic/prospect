<?php

namespace App\Http\Controllers;

use App\Models\Company;
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
}
