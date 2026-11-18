<?php

namespace Tests\Feature\Database;

use App\Models\AuditLog;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormoraSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_membership_relationships_and_uuid_models_work(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $membership = WorkspaceMember::factory()
            ->for($workspace)
            ->for($user)
            ->create(['role' => 'owner']);

        $this->assertNotEmpty($workspace->id);
        $this->assertSame($workspace->id, $membership->workspace->id);
        $this->assertSame($user->id, $membership->user->id);
        $this->assertSame('owner', $workspace->members()->firstOrFail()->pivot->role);
        $this->assertTrue($user->workspaces()->whereKey($workspace->id)->exists());
    }

    public function test_a_user_can_only_have_one_membership_per_workspace(): void
    {
        $membership = WorkspaceMember::factory()->create();

        $this->expectException(QueryException::class);

        WorkspaceMember::factory()->create([
            'workspace_id' => $membership->workspace_id,
            'user_id' => $membership->user_id,
        ]);
    }

    public function test_submission_round_trips_json_and_resolves_its_form_version(): void
    {
        $form = Form::factory()->create();
        $version = FormVersion::factory()->for($form)->create([
            'schema' => [
                'schema_version' => 1,
                'pages' => [['id' => 'page-1', 'fields' => []]],
                'settings' => [],
            ],
        ]);
        $submission = FormSubmission::factory()->create([
            'workspace_id' => $form->workspace_id,
            'form_id' => $form->id,
            'form_version_id' => $version->id,
            'response_data' => ['answers' => ['field-1' => 'contoh']],
        ]);

        $this->assertSame($form->id, $submission->form->id);
        $this->assertSame($version->id, $submission->formVersion->id);
        $this->assertSame($version->id, $form->submissions()->firstOrFail()->form_version_id);
        $this->assertSame('contoh', $submission->fresh()->response_data['answers']['field-1']);
        $this->assertSame(1, $version->fresh()->schema['schema_version']);
    }

    public function test_submission_factory_creates_a_version_for_its_form(): void
    {
        $submission = FormSubmission::factory()->create();

        $this->assertSame($submission->form->workspace_id, $submission->workspace_id);
        $this->assertSame($submission->form_id, $submission->formVersion->form_id);
    }

    public function test_submission_cannot_reference_a_version_from_another_form(): void
    {
        $form = Form::factory()->create();
        $otherForm = Form::factory()->for($form->workspace)->create();
        $version = FormVersion::factory()->for($form)->create();

        $this->expectException(QueryException::class);

        FormSubmission::factory()->create([
            'workspace_id' => $otherForm->workspace_id,
            'form_id' => $otherForm->id,
            'form_version_id' => $version->id,
        ]);
    }

    public function test_submission_cannot_cross_workspace_boundary(): void
    {
        $form = Form::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $version = FormVersion::factory()->for($form)->create();

        $this->expectException(QueryException::class);

        FormSubmission::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'form_id' => $form->id,
            'form_version_id' => $version->id,
        ]);
    }

    public function test_status_columns_reject_values_outside_their_defined_sets(): void
    {
        $this->expectException(QueryException::class);

        Form::factory()->create(['status' => 'unknown']);
    }

    public function test_membership_role_rejects_values_outside_its_defined_set(): void
    {
        $this->expectException(QueryException::class);

        WorkspaceMember::factory()->create(['role' => 'superadmin']);
    }

    public function test_submission_status_rejects_values_outside_its_defined_set(): void
    {
        $submission = FormSubmission::factory()->create();

        $this->expectException(QueryException::class);

        $submission->update(['status' => 'unknown']);
    }

    public function test_audit_log_contains_no_payload_or_submission_values_column(): void
    {
        $auditLog = AuditLog::factory()->create();

        $this->assertSame('form.created', $auditLog->event);
        $this->assertArrayNotHasKey('payload', $auditLog->getAttributes());
        $this->assertArrayNotHasKey('response_data', $auditLog->getAttributes());
    }
}
