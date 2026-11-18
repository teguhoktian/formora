<?php

namespace App\Policies;

use App\Models\FormTemplate;
use App\Models\User;

class FormTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->workspaceMemberships()->exists();
    }

    public function view(User $user, FormTemplate $template): bool
    {
        if ($template->kind === 'official' && $template->status === 'published') {
            return true;
        }

        if ($template->visibility === 'public' && $template->status === 'published') {
            return true;
        }

        if ($template->workspace_id === null) {
            return false;
        }

        $membership = $user->workspaceMemberships()
            ->where('workspace_id', $template->workspace_id)
            ->first();
        if ($membership === null) {
            return false;
        }

        if (in_array($template->status, ['pending', 'rejected', 'draft', 'archived'], true)) {
            return $template->created_by === $user->id || in_array($membership->role, ['owner', 'admin'], true);
        }

        if ($template->visibility === 'private') {
            return $template->created_by === $user->id || in_array($membership->role, ['owner', 'admin'], true);
        }

        return $template->visibility === 'unlisted';
    }

    public function create(User $user, string $workspaceId): bool
    {
        return $user->workspaceMemberships()
            ->where('workspace_id', $workspaceId)
            ->whereIn('role', ['owner', 'admin', 'member'])
            ->exists();
    }

    public function update(User $user, FormTemplate $template): bool
    {
        if ($template->kind !== 'workspace' || $template->workspace_id === null) {
            return false;
        }

        $role = $user->workspaceMemberships()
            ->where('workspace_id', $template->workspace_id)
            ->value('role');

        return in_array($role, ['owner', 'admin', 'member'], true)
            && ($template->created_by === $user->id || in_array($role, ['owner', 'admin'], true));
    }

    public function review(User $user): bool
    {
        return (bool) $user->is_platform_moderator;
    }
}
