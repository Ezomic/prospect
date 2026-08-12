<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInteractionRequest;
use App\Models\Company;
use App\Models\Interaction;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class InteractionController extends Controller
{
    public function store(StoreInteractionRequest $request, Company $company): RedirectResponse
    {
        $company->interactions()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Interaction logged.')]);

        return back();
    }

    public function destroy(Interaction $interaction): RedirectResponse
    {
        $interaction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Interaction removed.')]);

        return back();
    }
}
