<?php

namespace Tests\Feature;

use App\Jobs\GenerateSubmissionExport;
use App\Models\Form;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_include_security_headers_and_privacy_notice(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertSee(route('privacy'));

        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Pemberitahuan Privasi')
            ->assertSee('Jadwal retensi dan penghapusan otomatis belum dikonfigurasi');
    }

    public function test_session_and_private_upload_defaults_are_hardened(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertFalse(config('filesystems.disks.local.serve'));
    }

    public function test_workspace_role_authorization_matrix_is_enforced(): void
    {
        $workspace = Workspace::factory()->create();
        $form = Form::factory()->create(['workspace_id' => $workspace->id]);
        $expected = [
            'owner' => [true, true, true, true],
            'admin' => [true, true, true, true],
            'member' => [true, true, false, false],
            'viewer' => [false, true, false, false],
        ];

        foreach ($expected as $role => [$canEdit, $canSeeResponses, $canPublish, $canExport]) {
            $user = User::factory()->create();
            WorkspaceMember::factory()->create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => $role,
            ]);

            $this->assertSame($canEdit, $user->can('update', $form), "{$role} update permission");
            $this->assertSame($canSeeResponses, $user->can('viewSubmissions', $form), "{$role} response permission");
            $this->assertSame($canPublish, $user->can('publish', $form), "{$role} publish permission");
            $this->assertSame($canExport, $user->can('downloadSubmissions', $form), "{$role} export permission");
        }
    }

    public function test_submission_export_requests_are_rate_limited(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        $form = Form::factory()->create(['workspace_id' => $workspace->id]);
        $url = route('forms.submissions.export', $form);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($user)
                ->withSession(['active_workspace_id' => $workspace->id])
                ->post($url)
                ->assertRedirect();
        }

        $this->actingAs($user)
            ->withSession(['active_workspace_id' => $workspace->id])
            ->post($url)
            ->assertTooManyRequests();

        Queue::assertPushed(GenerateSubmissionExport::class, 5);
    }
}
