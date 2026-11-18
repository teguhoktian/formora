<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormTemplate;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\FormSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_templates_can_be_searched_filtered_and_previewed(): void
    {
        [$owner] = $this->makeWorkspaceMember('owner');

        $this->actingAs($owner)->get(route('templates.index'))
            ->assertOk()
            ->assertSee('Pendaftaran kegiatan')
            ->assertSee('Survei kepuasan')
            ->assertSee('Permintaan informasi');

        $this->get(route('templates.index', ['q' => 'kepuasan', 'category' => 'Survei']))
            ->assertOk()->assertSee('Survei kepuasan')->assertDontSee('Pendaftaran kegiatan');

        $official = FormTemplate::query()->where('slug', 'official-pendaftaran-kegiatan')->firstOrFail();
        $this->get(route('templates.show', $official->slug))
            ->assertOk()->assertSee('Preview struktur form')->assertSee('Alamat email');
        $this->put(route('templates.update', $official->slug), [
            'title' => $official->title,
            'category' => $official->category,
            'visibility' => 'private',
        ])->assertForbidden();
    }

    public function test_member_can_save_a_form_use_template_and_keep_snapshots_independent(): void
    {
        [$owner, $workspace] = $this->makeWorkspaceMember('owner');
        $sourceForm = $this->makeForm($workspace, 'Sumber onboarding', 'Data pemohon');

        $this->actingAs($owner)->post(route('templates.from-form'), [
            'form_id' => $sourceForm->id,
            'title' => 'Template onboarding',
            'description' => 'Alur untuk pemohon baru.',
            'category' => 'Operasional',
        ])->assertRedirect();

        $template = FormTemplate::query()->where('title', 'Template onboarding')->firstOrFail();
        $this->assertSame('private', $template->visibility);
        $this->assertSame('draft', $template->status);
        $templateSnapshot = $template->latestVersion()->firstOrFail()->schema;
        $templateFieldId = $templateSnapshot['pages'][0]['fields'][0]['id'];

        $editedSourceSchema = $sourceForm->versions()->firstOrFail()->schema;
        $editedSourceSchema['pages'][0]['fields'][0]['label'] = 'Nama baru';
        $sourceForm->versions()->firstOrFail()->update(['schema' => $editedSourceSchema]);
        $this->assertSame('Data pemohon', $template->latestVersion()->firstOrFail()->schema['pages'][0]['fields'][0]['label']);

        $this->post(route('templates.use', $template->slug))->assertRedirect();
        $copiedForm = Form::query()->where('title', 'Template onboarding')->where('id', '!=', $sourceForm->id)->firstOrFail();
        $copiedSchema = $copiedForm->versions()->firstOrFail()->schema;
        $this->assertNotSame($templateFieldId, $copiedSchema['pages'][0]['fields'][0]['id']);
        $this->assertSame('Data pemohon', $copiedSchema['pages'][0]['fields'][0]['label']);

        $copiedSchema['pages'][0]['fields'][0]['label'] = 'Khusus form baru';
        $copiedForm->versions()->firstOrFail()->update(['schema' => $copiedSchema]);
        $this->assertSame('Data pemohon', $template->latestVersion()->firstOrFail()->schema['pages'][0]['fields'][0]['label']);
    }

    public function test_template_versions_are_added_as_immutable_schema_snapshots(): void
    {
        [$owner, $workspace] = $this->makeWorkspaceMember('owner');
        $source = $this->makeForm($workspace, 'Sumber', 'Nama');
        $this->actingAs($owner)->post(route('templates.from-form'), [
            'form_id' => $source->id, 'title' => 'Template versi', 'category' => 'Operasional',
        ]);
        $template = FormTemplate::query()->where('title', 'Template versi')->firstOrFail();
        $oldVersion = $template->latestVersion()->firstOrFail();

        $newSource = $this->makeForm($workspace, 'Sumber baru', 'Nama lengkap');
        $this->post(route('templates.versions.store', $template->slug), ['form_id' => $newSource->id])
            ->assertRedirect();

        $this->assertSame(2, $template->versions()->max('version_number'));
        $this->assertSame('Nama', $oldVersion->fresh()->schema['pages'][0]['fields'][0]['label']);
        $this->assertSame('Nama lengkap', $template->latestVersion()->firstOrFail()->schema['pages'][0]['fields'][0]['label']);
        $this->expectException(\LogicException::class);
        $oldVersion->update(['schema' => []]);
    }

    public function test_public_templates_require_moderation_before_community_listing(): void
    {
        [$owner, $workspace] = $this->makeWorkspaceMember('owner');
        $this->makeWorkspaceMember('owner');
        $source = $this->makeForm($workspace, 'Sumber komunitas', 'Nama');
        $this->actingAs($owner)->post(route('templates.from-form'), [
            'form_id' => $source->id, 'title' => 'Template komunitas', 'category' => 'Umum',
        ]);
        $template = FormTemplate::query()->where('title', 'Template komunitas')->firstOrFail();

        $this->put(route('templates.update', $template->slug), [
            'title' => $template->title,
            'description' => $template->description,
            'category' => $template->category,
            'visibility' => 'public',
        ])->assertRedirect();
        $this->assertSame('pending', $template->fresh()->status);

        [$otherUser] = $this->makeWorkspaceMember('owner');
        $this->actingAs($otherUser)->get(route('templates.index'))
            ->assertOk()->assertDontSee('Template komunitas');
        $this->get(route('templates.moderation'))->assertForbidden();

        $moderator = User::factory()->create();
        $moderator->forceFill(['is_platform_moderator' => true])->save();
        $this->actingAs($moderator)->get(route('templates.moderation'))
            ->assertOk()->assertSee('Template komunitas');
        $this->post(route('templates.moderate', $template->slug), ['decision' => 'approve'])
            ->assertRedirect(route('templates.moderation'));
        $this->assertSame('published', $template->fresh()->status);

        $this->actingAs($otherUser)->get(route('templates.index'))
            ->assertOk()->assertSee('Template komunitas');
    }

    public function test_private_template_and_source_form_are_isolated_to_the_workspace(): void
    {
        [$owner, $workspace] = $this->makeWorkspaceMember('owner');
        [$sameWorkspaceMember] = $this->makeWorkspaceMember('member');
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $sameWorkspaceMember->id,
            'role' => 'member',
        ]);
        [$outsider] = $this->makeWorkspaceMember('owner');
        $source = $this->makeForm($workspace, 'Workspace form', 'Nama');
        $foreignSource = $this->makeForm(Workspace::factory()->create(), 'Form privat', 'Nama');

        $this->actingAs($owner)->post(route('templates.from-form'), [
            'form_id' => $source->id, 'title' => 'Template privat', 'category' => 'Umum',
        ]);
        $template = FormTemplate::query()->where('title', 'Template privat')->firstOrFail();

        $this->post(route('templates.from-form'), [
            'form_id' => $foreignSource->id, 'title' => 'Template lintas workspace', 'category' => 'Umum',
        ])->assertNotFound();
        $this->actingAs($sameWorkspaceMember)->get(route('templates.show', $template->slug))->assertForbidden();

        $this->actingAs($owner)->put(route('templates.update', $template->slug), [
            'title' => $template->title,
            'description' => $template->description,
            'category' => $template->category,
            'visibility' => 'unlisted',
        ])->assertRedirect();
        $this->actingAs($sameWorkspaceMember)->get(route('templates.show', $template->slug))->assertOk();
        $this->get(route('templates.index'))->assertOk()->assertDontSee('Template privat');
        WorkspaceMember::query()->where('workspace_id', $workspace->id)->where('user_id', $owner->id)->update(['role' => 'viewer']);
        $this->actingAs($owner)->put(route('templates.update', $template->slug), [
            'title' => $template->title,
            'category' => $template->category,
            'visibility' => 'unlisted',
        ])->assertForbidden();
        $this->actingAs($outsider)->get(route('templates.show', $template->slug))->assertForbidden();
    }

    public function test_platform_moderator_can_be_granted_and_revoked_from_cli(): void
    {
        $user = User::factory()->create();

        $this->artisan('formora:templates:moderator', ['email' => $user->email])->assertExitCode(0);
        $this->assertTrue($user->fresh()->is_platform_moderator);

        $this->artisan('formora:templates:moderator', ['email' => $user->email, '--revoke' => true])->assertExitCode(0);
        $this->assertFalse($user->fresh()->is_platform_moderator);
    }

    public function test_viewer_cannot_save_or_use_templates_to_create_forms(): void
    {
        [$viewer, $workspace] = $this->makeWorkspaceMember('viewer');
        $source = $this->makeForm($workspace, 'Sumber viewer', 'Nama');
        $official = FormTemplate::query()->where('slug', 'official-survei-kepuasan')->firstOrFail();

        $this->actingAs($viewer)->post(route('templates.from-form'), [
            'form_id' => $source->id, 'title' => 'Tidak diizinkan', 'category' => 'Umum',
        ])->assertForbidden();
        $this->post(route('templates.use', $official->slug))->assertForbidden();
    }

    /** @return array{User, Workspace} */
    private function makeWorkspaceMember(string $role): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return [$user, $workspace];
    }

    private function makeForm(Workspace $workspace, string $title, string $fieldLabel): Form
    {
        $schemas = app(FormSchemaService::class);
        $schema = $schemas->empty();
        $field = $schemas->newField('text', $schema);
        $field['name'] = 'nama_pemohon';
        $field['label'] = $fieldLabel;
        $field['required'] = true;
        $schema['pages'][0]['fields'][] = $field;

        $form = $workspace->forms()->create(['title' => $title, 'status' => 'draft']);
        $form->versions()->create(['version_number' => 1, 'schema' => $schema, 'is_published' => false]);

        return $form;
    }
}
