<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = $this->statusCounts();

        return Inertia::render('Dashboard', [
            'total' => $stats->sum('count'),
            'stats' => $stats,
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
        $counts = $this->countsByStatus();

        return collect(CompanyStatus::cases())->map(fn (CompanyStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'count' => $counts[$status->value] ?? 0,
        ])->values();
    }

    /**
     * @return array<string, int>
     */
    private function countsByStatus(): array
    {
        $rows = DB::table('companies')
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $status = $row->status;
            $aggregate = $row->aggregate;

            if (is_string($status) && is_numeric($aggregate)) {
                $counts[$status] = (int) $aggregate;
            }
        }

        return $counts;
    }
}
