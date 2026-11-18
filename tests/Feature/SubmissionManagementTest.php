<?php

namespace Tests\Feature;

use App\Jobs\GenerateSubmissionExport;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
use App\Models\SubmissionExport;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\SubmissionResponsePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_list_filters_searches_and_paginates_with_workspace_scope(): void
    {
        [$owner, $workspace, $form] = $this->makeForm('owner');
        $matching = $this->submission($form, ['name' => 'Budi']);
        $this->submission($form, ['name' => 'Siti'])->update(['status' => 'approved']);
        [, , $otherForm] = $this->makeForm('owner');
        $otherSubmission = $this->submission($otherForm, ['name' => 'Budi']);

        $response = $this->actingAs($owner)->withSession(['active_workspace_id' => $workspace->id])
            ->get(route('forms.submissions.index', [$form, 'search' => 'Budi', 'status' => 'new']));

        $response->assertOk()->assertSee($matching->reference_number)->assertDontSee($otherSubmission->reference_number);
        $this->assertSame(1, $response->viewData('submissions')->total());
    }

    public function test_submission_detail_masks_sensitive_and_private_file_values(): void
    {
        [$owner, $workspace, $form, $version] = $this->makeForm('owner', [
            ['name' => 'nik', 'label' => 'NIK'],
            ['name' => 'attachment', 'label' => 'Dokumen'],
            ['name' => 'email', 'label' => 'Email'],
        ]);
        $submission = FormSubmission::factory()->create([
            'workspace_id' => $workspace->id,
            'form_id' => $form->id,
            'form_version_id' => $version->id,
            'response_data' => ['nik' => '1234567890123456', 'attachment' => ['private_path' => 'secret/path'], 'email' => 'budi@example.test'],
        ]);

        $this->actingAs($owner)->withSession(['active_workspace_id' => $workspace->id])
            ->get(route('forms.submissions.show', [$form, $submission]))
            ->assertOk()->assertSee('••••••••')->assertSee('File terlampir')->assertSee('budi@example.test')
            ->assertDontSee('1234567890123456')->assertDontSee('secret/path');
    }

    public function test_viewer_can_read_masked_submission_but_cannot_export_or_change_it(): void
    {
        [$owner, $workspace, $form] = $this->makeForm('viewer');
        $submission = $this->submission($form, ['name' => 'Budi']);

        $this->actingAs($owner)->withSession(['active_workspace_id' => $workspace->id])
            ->get(route('forms.submissions.show', [$form, $submission]))->assertOk();
        $this->post(route('forms.submissions.export', $form))->assertForbidden();
        $this->patch(route('forms.submissions.update', [$form, $submission]), ['status' => 'approved'])->assertForbidden();
    }

    public function test_member_can_update_submission_status_and_audit_contains_no_response_values(): void
    {
        [$member, $workspace, $form] = $this->makeForm('member');
        $submission = $this->submission($form, ['private_answer' => 'do-not-log-this']);

        $this->actingAs($member)->withSession(['active_workspace_id' => $workspace->id])
            ->patch(route('forms.submissions.update', [$form, $submission]), [
                'status' => 'in_review', 'internal_notes' => 'Perlu ditinjau',
            ])->assertRedirect();

        $this->assertSame('in_review', $submission->fresh()->status);
        $this->assertSame('Perlu ditinjau', $submission->fresh()->internal_notes);
        $audit = $workspace->auditLogs()->latest('id')->firstOrFail();
        $this->assertSame('submission.updated', $audit->event);
        $this->assertStringNotContainsString('do-not-log-this', json_encode($audit->getAttributes(), JSON_THROW_ON_ERROR));
    }

    public function test_export_is_queued_and_download_is_workspace_authorized(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$owner, $workspace, $form, $version] = $this->makeForm('owner', [
            ['name' => 'nik', 'label' => 'NIK'], ['name' => 'name', 'label' => 'Nama'], ['name' => 'formula', 'label' => 'Formula'],
        ]);
        $submission = FormSubmission::factory()->create([
            'workspace_id' => $workspace->id, 'form_id' => $form->id, 'form_version_id' => $version->id,
            'reference_number' => 'FM-MATCH', 'response_data' => ['nik' => '1234567890123456', 'name' => 'Budi', 'formula' => '=2+2'],
        ]);

        $this->actingAs($owner)->withSession(['active_workspace_id' => $workspace->id])
            ->post(route('forms.submissions.export', [$form, 'search' => 'Budi']))->assertRedirect();
        $export = SubmissionExport::query()->firstOrFail();
        $this->assertEquals(['status' => null, 'search' => 'Budi', 'from' => null, 'to' => null], $export->filters);
        Queue::assertPushed(GenerateSubmissionExport::class);

        app(GenerateSubmissionExport::class, ['exportId' => $export->id])->handle(app(SubmissionResponsePresenter::class));
        $export = $export->fresh();
        $this->assertSame('ready', $export->status);
        $csv = Storage::disk('local')->get($export->file_path);
        $csvRows = array_map(fn (string $line): array => str_getcsv($line), explode("\n", trim($csv)));
        $this->assertSame(['Referensi', 'Status', 'Dikirim', 'NIK', 'Nama', 'Formula'], $csvRows[0]);
        $this->assertCount(6, $csvRows[1]);
        $this->assertSame('FM-MATCH', $csvRows[1][0]);
        $this->assertSame('••••••••', $csvRows[1][3]);
        $this->assertSame('Budi', $csvRows[1][4]);
        $this->assertSame("'=2+2", $csvRows[1][5]);
        $this->assertStringNotContainsString('1234567890123456', $csv);

        $this->get(route('forms.submissions.exports.download', [$form, $export]))->assertOk();
        $this->assertNotNull($submission->fresh());
    }

    /** @param list<array{name: string, label: string}> $fields
     * @return array{User, Workspace, Form, FormVersion}
     */
    private function makeForm(string $role, array $fields = []): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $user->id, 'role' => $role]);
        $form = Form::factory()->create(['workspace_id' => $workspace->id]);
        $version = FormVersion::factory()->for($form)->create([
            'schema' => [
                'schema_version' => 1,
                'pages' => [['id' => fake()->uuid(), 'title' => 'Halaman 1', 'description' => null, 'fields' => array_map(fn (array $field): array => $field + ['id' => fake()->uuid(), 'type' => 'text'], $fields)]],
                'settings' => [],
            ],
        ]);

        return [$user, $workspace, $form, $version];
    }

    /** @param array<string, mixed> $answers */
    private function submission(Form $form, array $answers): FormSubmission
    {
        $version = $form->versions()->firstOrFail();

        return FormSubmission::factory()->create([
            'workspace_id' => $form->workspace_id,
            'form_id' => $form->id,
            'form_version_id' => $version->id,
            'response_data' => $answers,
        ]);
    }
}
