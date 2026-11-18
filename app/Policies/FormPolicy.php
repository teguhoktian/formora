<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;

class FormPolicy
{
    public function view(User $user, Form $form): bool
    {
        return $user->can('view', $form->workspace);
    }

    public function update(User $user, Form $form): bool
    {
        return $user->workspaceMemberships()
            ->where('workspace_id', $form->workspace_id)
            ->whereIn('role', ['owner', 'admin', 'member'])
            ->exists();
    }

    public function delete(User $user, Form $form): bool
    {
        return $user->can('update', $form);
    }
}
