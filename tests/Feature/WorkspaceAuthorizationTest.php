<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\WorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WorkspaceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_users_workspace_or_form(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $workspaceB = Workspace::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspaceB->id,
            'user_id' => $userB->id,
            'role' => 'owner',
        ]);
        $formB = Form::factory()->create(['workspace_id' => $workspaceB->id]);

        $this->actingAs($userA);

        $this->assertFalse($userA->can('view', $workspaceB));
        $this->assertFalse($userA->can('view', $formB));
    }

    public function test_workspace_switch_only_accepts_a_workspace_the_user_belongs_to(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $workspaceB = Workspace::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspaceB->id,
            'user_id' => $userB->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($userA)->from('/dashboard')->post(route('workspaces.switch'), [
            'workspace_id' => $workspaceB->id,
        ]);

        $response->assertSessionHasErrors('workspace_id');
        $this->assertNotSame($workspaceB->id, session('active_workspace_id'));
    }

    public function test_viewer_cannot_update_forms_or_manage_workspace_members(): void
    {
        $viewer = User::factory()->create();
        $workspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $viewer->id,
            'role' => 'viewer',
        ]);
        $form = Form::factory()->create(['workspace_id' => $workspace->id]);

        $this->assertTrue($viewer->can('view', $form));
        $this->assertFalse($viewer->can('update', $form));
        $this->assertFalse($viewer->can('manageMembers', $workspace));
    }

    public function test_member_cannot_manage_members(): void
    {
        $member = User::factory()->create();
        $workspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);
        $target = WorkspaceMember::factory()->create(['workspace_id' => $workspace->id]);

        $this->assertFalse($member->can('manage', $target));
    }

    public function test_owner_must_transfer_ownership_before_removal_and_can_transfer_to_member(): void
    {
        $owner = User::factory()->create();
        $newOwner = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $ownerMembership = WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
        $newOwnerMembership = WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $newOwner->id,
            'role' => 'member',
        ]);
        $service = app(WorkspaceService::class);

        try {
            $service->removeMember($owner, $ownerMembership);
            $this->fail('An owner must not be removed before ownership transfer.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $service->transferOwnership($owner, $workspace, $newOwnerMembership);

        $this->assertSame('admin', $ownerMembership->fresh()->role);
        $this->assertSame('owner', $newOwnerMembership->fresh()->role);

        $service->removeMember($owner, $ownerMembership->fresh());
        $this->assertDatabaseMissing('workspace_members', ['id' => $ownerMembership->id]);
    }

    public function test_workspace_creation_gives_creator_owner_role(): void
    {
        $user = User::factory()->create();

        $workspace = app(WorkspaceService::class)->createWorkspace($user, 'Tim Formora');

        $this->assertTrue($user->can('view', $workspace));
        $this->assertSame('owner', $user->workspaceMemberships()->where('workspace_id', $workspace->id)->value('role'));
    }
}
