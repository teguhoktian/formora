<?php

namespace Tests\Feature;

use App\Livewire\FormBuilder;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\FormSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceNavigationAndDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_page_is_workspace_scoped_and_does_not_render_answers(): void
    {
        [$user, $workspace, $form] = $this->makeWorkspaceForm('owner');
        $submission = FormSubmission::factory()->for($form)->create([
            'workspace_id' => $workspace->id,
            'form_version_id' => $form->versions()->firstOrFail()->id,
            'reference_number' => 'FM-LOCAL-1',
            'response_data' => ['answer' => 'private answer'],
        ]);
        [$otherUser, $otherWorkspace, $otherForm] = $this->makeWorkspaceForm('owner');
        $otherSubmission = FormSubmission::factory()->for($otherForm)->create([
            'workspace_id' => $otherWorkspace->id,
            'form_version_id' => $otherForm->versions()->firstOrFail()->id,
            'reference_number' => 'FM-OTHER-1',
        ]);

        $this->actingAs($user)->withSession(['active_workspace_id' => $workspace->id])
            ->get(route('responses.index'))
            ->assertOk()
            ->assertSee($submission->reference_number)
            ->assertDontSee($otherSubmission->reference_number)
            ->assertDontSee('private answer');
    }

    public function test_workspace_page_lists_only_members_of_the_active_workspace(): void
    {
        [$user, $workspace] = $this->makeWorkspaceForm('owner');
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create(['workspace_id' => $otherWorkspace->id, 'user_id' => $otherUser->id, 'role' => 'owner']);

        $this->actingAs($user)->withSession(['active_workspace_id' => $workspace->id])
            ->get(route('workspaces.index'))
            ->assertOk()
            ->assertSee($user->email)
            ->assertDontSee($otherUser->email);
    }

    public function test_design_choices_are_validated_and_saved_to_the_draft_schema(): void
    {
        [$owner, , $form] = $this->makeWorkspaceForm('owner');

        Livewire::actingAs($owner)
            ->test(FormBuilder::class, ['formId' => $form->id])
            ->call('setTab', 'design')
            ->assertSet('activeTab', 'design')
            ->set('designAccent', 'blue')
            ->set('designWidth', 'wide');

        $schema = $form->versions()->firstOrFail()->fresh()->schema;
        $this->assertSame(['accent' => 'blue', 'width' => 'wide'], $schema['settings']['appearance']);
    }

    /** @return array{User, Workspace, Form} */
    private function makeWorkspaceForm(string $role): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => $role]);
        $form = Form::factory()->create(['workspace_id' => $workspace->id]);
        $form->versions()->create([
            'version_number' => 1,
            'schema' => app(FormSchemaService::class)->empty(),
            'is_published' => false,
        ]);

        return [$user, $workspace, $form];
    }
}
