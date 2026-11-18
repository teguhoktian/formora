<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_only_shows_data_from_the_active_workspace(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'user_id' => $otherUser->id,
            'role' => 'owner',
        ]);
        Form::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'title' => 'Private workspace form',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee('Private workspace form')
            ->assertSee('Buat workspace pertama Anda');
    }

    public function test_dashboard_shows_counts_and_forms_only_for_current_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['name' => 'Tim Contoh']);
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        Form::factory()->create([
            'workspace_id' => $workspace->id,
            'title' => 'Form Pendaftaran',
            'status' => 'published',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Tim Contoh')
            ->assertSee('Form Pendaftaran')
            ->assertSee('Form dipublikasikan');
    }
}
