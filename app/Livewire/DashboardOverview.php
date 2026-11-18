<?php

namespace App\Livewire;

use App\Services\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DashboardOverview extends Component
{
    public function render(CurrentWorkspace $currentWorkspace): View
    {
        $user = auth()->user();
        $workspace = $user === null ? null : $currentWorkspace->get($user, request());

        return view('livewire.dashboard-overview', [
            'workspace' => $workspace,
            'formCount' => $workspace?->forms()->count() ?? 0,
            'publishedCount' => $workspace?->forms()->where('status', 'published')->count() ?? 0,
            'submissionCount' => $workspace?->submissions()->count() ?? 0,
            'recentForms' => $workspace?->forms()->latest()->limit(5)->get() ?? collect(),
            'recentSubmissions' => $workspace?->submissions()->with('form')->latest()->limit(5)->get() ?? collect(),
            'recentActivity' => $workspace?->auditLogs()->latest('created_at')->limit(5)->get() ?? collect(),
        ]);
    }
}
