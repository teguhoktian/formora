<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;
use App\Models\Workspace;

class FormPolicy
{
    public function create(User $user, Workspace $workspace): bool
    {
        return $user->workspaceMemberships()
            ->where('workspace_id', $workspace->id)
            ->whereIn('role', ['owner', 'admin', 'member'])
            ->exists();
    }

    public function view(User $user, Form $form): bool
    {
        return $user->can('view', $form->workspace);
    }

    public function viewSubmissions(User $user, Form $form): bool
    {
        return $user->workspaceMemberships()
            ->where('workspace_id', $form->workspace_id)
            ->exists();
    }

    public function downloadSubmissions(User $user, Form $form): bool
    {
        return $user->workspaceMemberships()
            ->where('workspace_id', $form->workspace_id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function update(User $user, Form $form): bool
    {
        return $user->workspaceMemberships()
            ->where('workspace_id', $form->workspace_id)
            ->whereIn('role', ['owner', 'admin', 'member'])
            ->exists();
    }

    public function publish(User $user, Form $form): bool
    {
        return $user->workspaceMemberships()
            ->where('workspace_id', $form->workspace_id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function delete(User $user, Form $form): bool
    {
        return $user->can('update', $form);
    }
}
