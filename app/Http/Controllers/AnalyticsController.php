<?php

namespace App\Http\Controllers;

use App\Services\CurrentWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->get($request->user(), $request);
        $now = CarbonImmutable::now();
        $monthlySubmissions = collect(range(5, 0))->map(function (int $monthsAgo) use ($now, $workspace): array {
            $month = $now->startOfMonth()->subMonths($monthsAgo);
            $nextMonth = $month->addMonth();

            return [
                'label' => $month->translatedFormat('M Y'),
                'count' => $workspace?->submissions()
                    ->where('created_at', '>=', $month)
                    ->where('created_at', '<', $nextMonth)
                    ->count() ?? 0,
            ];
        });
        $statusCounts = $workspace?->submissions()->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status') ?? collect();

        return view('analytics.index', [
            'workspace' => $workspace,
            'formCount' => $workspace?->forms()->count() ?? 0,
            'publishedCount' => $workspace?->forms()->where('status', 'published')->count() ?? 0,
            'submissionCount' => $workspace?->submissions()->count() ?? 0,
            'recentSubmissionCount' => $workspace?->submissions()->where('created_at', '>=', $now->subDays(29)->startOfDay())->count() ?? 0,
            'monthlySubmissions' => $monthlySubmissions,
            'maxMonthlyCount' => max(1, (int) $monthlySubmissions->max('count')),
            'statusCounts' => $statusCounts,
            'topForms' => $workspace?->forms()->withCount('submissions')->orderByDesc('submissions_count')->limit(5)->get() ?? collect(),
        ]);
    }
}
