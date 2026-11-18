<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;

class CurrentWorkspace
{
    public function get(User $user, Request $request): ?Workspace
    {
        $session = $request->hasSession() ? $request->session() : null;
        $workspaceId = $session?->get('active_workspace_id');
        $membership = $user->workspaceMemberships()
            ->with('workspace')
            ->when(is_string($workspaceId), fn ($query) => $query->where('workspace_id', $workspaceId))
            ->first();

        if ($membership === null && $workspaceId !== null) {
            $session->forget('active_workspace_id');
        }

        $membership ??= $user->workspaceMemberships()->with('workspace')->oldest('id')->first();

        if ($membership !== null) {
            $session?->put('active_workspace_id', $membership->workspace_id);
        }

        return $membership?->workspace;
    }
}
