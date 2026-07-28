<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'total' => Company::count(),
            'stats' => $this->statusCounts(),
            'followUpsDue' => Company::query()
                ->whereNotNull('follow_up_at')
                ->whereDate('follow_up_at', '<=', today())
                ->where('status', '!=', CompanyStatus::Closed)
                ->count(),
        ]);
    }

    /**
     * @return Collection<int, array{value: string, label: string, count: int}>
     */
    private function statusCounts(): Collection
    {
        return collect(CompanyStatus::cases())->map(fn (CompanyStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'count' => Company::where('status', $status)->count(),
        ])->values();
    }
}
