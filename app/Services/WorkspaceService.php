<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;

class WorkspaceService
{
    public function createPersonalWorkspace(User $user): Workspace
    {
        return DB::transaction(function () use ($user): Workspace {
            $workspace = Workspace::create(['name' => 'Ruang kerja '.$user->name]);
            $workspace->workspaceMembers()->create([
                'user_id' => $user->id,
                'role' => 'owner',
            ]);

            return $workspace;
        });
    }

    public function createWorkspace(User $user, string $name): Workspace
    {
        return DB::transaction(function () use ($user, $name): Workspace {
            $workspace = Workspace::create(['name' => $name]);
            $workspace->workspaceMembers()->create([
                'user_id' => $user->id,
                'role' => 'owner',
            ]);

            return $workspace;
        });
    }

    public function transferOwnership(User $actor, Workspace $workspace, WorkspaceMember $newOwner): void
    {
        abort_unless($actor->can('transferOwnership', $workspace), 403);
        abort_unless($newOwner->workspace_id === $workspace->id, 404);

        DB::transaction(function () use ($actor, $workspace, $newOwner): void {
            $currentOwner = $workspace->workspaceMembers()
                ->where('role', 'owner')
                ->where('user_id', $actor->id)
                ->lockForUpdate()
                ->firstOrFail();
            $target = $workspace->workspaceMembers()
                ->whereKey($newOwner->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($target->role === 'owner', 422);

            $currentOwner->update(['role' => 'admin']);
            $target->update(['role' => 'owner']);
        });
    }

    public function removeMember(User $actor, WorkspaceMember $member): void
    {
        abort_unless($actor->can('delete', $member), 403);
        abort_if($member->role === 'owner', 422, 'Ownership must be transferred before removing the owner.');

        $member->delete();
    }

    public function membershipFor(User $user, string $workspaceId): WorkspaceMember
    {
        return $user->workspaceMemberships()
            ->where('workspace_id', $workspaceId)
            ->firstOrFail();
    }
}
