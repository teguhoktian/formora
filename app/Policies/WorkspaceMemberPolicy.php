<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceMember;

class WorkspaceMemberPolicy
{
    public function view(User $user, WorkspaceMember $member): bool
    {
        return $user->workspaceMemberships()->where('workspace_id', $member->workspace_id)->exists();
    }

    public function manage(User $user, WorkspaceMember $member): bool
    {
        return $user->can('manageMembers', $member->workspace);
    }

    public function delete(User $user, WorkspaceMember $member): bool
    {
        return $member->role !== 'owner' && $this->manage($user, $member);
    }
}
