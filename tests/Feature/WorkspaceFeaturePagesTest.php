<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceFeaturePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_fields_catalog_is_available_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('smart-fields.index'))
            ->assertOk()
            ->assertSee('Smart Fields Indonesia')
            ->assertSee('12 field tersedia')
            ->assertSee('NIK')
            ->assertSee('Nama bank')
            ->assertSee('Data sensitif');
    }

    public function test_analytics_are_limited_to_the_active_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        $form = Form::factory()->create([
            'workspace_id' => $workspace->id,
            'title' => 'Form internal workspace',
            'status' => 'published',
        ]);
        $version = FormVersion::factory()->for($form)->create();
        FormSubmission::factory()->create([
            'workspace_id' => $workspace->id,
            'form_id' => $form->id,
            'form_version_id' => $version->id,
            'status' => 'new',
            'created_at' => now(),
        ]);

        $otherWorkspace = Workspace::factory()->create();
        $otherForm = Form::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'title' => 'Form workspace lain',
            'status' => 'published',
        ]);
        $otherVersion = FormVersion::factory()->for($otherForm)->create();
        FormSubmission::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'form_id' => $otherForm->id,
            'form_version_id' => $otherVersion->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_workspace_id' => $workspace->id])
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Form internal workspace')
            ->assertDontSee('Form workspace lain')
            ->assertSee('Total respons')
            ->assertSee('Respons 30 hari terakhir')
            ->assertSee('Enam bulan kalender terakhir');

        $this->assertSame(1, $response->viewData('formCount'));
        $this->assertSame(1, $response->viewData('publishedCount'));
        $this->assertSame(1, $response->viewData('submissionCount'));
        $this->assertSame(1, $response->viewData('recentSubmissionCount'));
        $this->assertSame(1, $response->viewData('statusCounts')['new']);
    }

    public function test_smart_fields_and_analytics_require_authentication(): void
    {
        $this->get(route('smart-fields.index'))->assertRedirect(route('login'));
        $this->get(route('analytics.index'))->assertRedirect(route('login'));
    }
}
