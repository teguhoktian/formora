<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $user->workspaceMemberships()->where('workspace_id', $workspace->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->workspaceMemberships()
            ->where('workspace_id', $workspace->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $this->update($user, $workspace);
    }

    public function transferOwnership(User $user, Workspace $workspace): bool
    {
        return $user->workspaceMemberships()
            ->where('workspace_id', $workspace->id)
            ->where('role', 'owner')
            ->exists();
    }
}
