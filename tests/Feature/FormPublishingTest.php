<?php

namespace Tests\Feature;

use App\Livewire\FormBuilder;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\FormBuilderService;
use App\Services\FormPublishingService;
use App\Services\FormSchemaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FormPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_creates_an_active_immutable_version_and_next_editable_draft(): void
    {
        [$owner, $form, $draft] = $this->makeForm('owner');
        $schema = app(FormSchemaService::class)->empty();
        $schema['pages'][0]['title'] = 'Versi pertama';
        $draft->update(['schema' => $schema]);

        $published = app(FormPublishingService::class)->publishDraft($form, $draft->id, app(FormSchemaService::class));

        $this->assertSame($draft->id, $published->id);
        $this->assertTrue($published->fresh()->is_published);
        $this->assertNotNull($published->fresh()->published_at);
        $this->assertSame($published->id, $form->fresh()->published_version_id);
        $this->assertSame('published', $form->fresh()->status);

        $newDraft = app(FormBuilderService::class)->ensureDraft($form->fresh(), app(FormSchemaService::class));
        $this->assertEquals($schema, $newDraft);
        $this->assertSame(2, $form->versions()->where('is_published', false)->firstOrFail()->version_number);

        try {
            $published->fresh()->update(['schema' => app(FormSchemaService::class)->empty()]);
            $this->fail('A published schema must reject edits.');
        } catch (\LogicException $exception) {
            $this->assertSame('Published form versions are immutable.', $exception->getMessage());
        }
    }

    public function test_republishing_keeps_historical_submission_attached_to_old_version(): void
    {
        [$owner, $form, $draft] = $this->makeForm('owner');
        $publishing = app(FormPublishingService::class);
        $schemas = app(FormSchemaService::class);
        $oldVersion = $publishing->publishDraft($form, $draft->id, $schemas);
        $submission = FormSubmission::factory()->create([
            'workspace_id' => $form->workspace_id,
            'form_id' => $form->id,
            'form_version_id' => $oldVersion->id,
        ]);

        app(FormBuilderService::class)->ensureDraft($form->fresh(), $schemas);
        $newDraft = $form->versions()->where('is_published', false)->firstOrFail();
        $newSchema = $newDraft->schema;
        $newSchema['pages'][0]['title'] = 'Versi kedua';
        $newDraft->update(['schema' => $newSchema]);
        $newVersion = $publishing->publishDraft($form->fresh(), $newDraft->id, $schemas);

        $this->assertNotSame($oldVersion->id, $newVersion->id);
        $this->assertSame($newVersion->id, $form->fresh()->published_version_id);
        $this->assertSame($oldVersion->id, $submission->fresh()->form_version_id);
        $this->assertSame($oldVersion->schema, $oldVersion->fresh()->schema);
    }

    public function test_published_version_cannot_be_changed_through_query_builder(): void
    {
        [$owner, $form, $draft] = $this->makeForm('owner');
        $version = app(FormPublishingService::class)->publishDraft($form, $draft->id, app(FormSchemaService::class));

        $this->expectException(QueryException::class);
        DB::table('form_versions')->where('id', $version->id)->update([
            'schema' => json_encode(app(FormSchemaService::class)->empty(), JSON_THROW_ON_ERROR),
        ]);
    }

    public function test_only_workspace_owners_and_admins_can_publish(): void
    {
        [$member, $form, $draft] = $this->makeForm('member');
        Livewire::actingAs($member)
            ->test(FormBuilder::class, ['formId' => $form->id])
            ->call('publishDraft', $draft->id)
            ->assertForbidden();

        [$admin, $adminForm, $adminDraft] = $this->makeForm('admin');
        Livewire::actingAs($admin)
            ->test(FormBuilder::class, ['formId' => $adminForm->id])
            ->call('publishDraft', $adminDraft->id)
            ->assertHasNoErrors('publish');
        $this->assertSame('published', $adminForm->fresh()->status);
    }

    public function test_racing_stale_publish_request_does_not_create_a_duplicate_version(): void
    {
        [$owner, $form, $draft] = $this->makeForm('owner');
        $publishing = app(FormPublishingService::class);
        $schemas = app(FormSchemaService::class);

        $first = $publishing->publishDraft($form, $draft->id, $schemas);
        app(FormBuilderService::class)->ensureDraft($form->fresh(), $schemas);
        try {
            $publishing->publishDraft($form->fresh(), $draft->id, $schemas);
            $this->fail('A stale concurrent publish must not publish the same draft twice.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('publish', $exception->errors());
        }

        $this->assertSame(2, $form->versions()->count());
        $this->assertSame(1, $form->versions()->where('is_published', true)->count());
        $this->assertSame($first->id, $form->fresh()->published_version_id);
    }

    public function test_pause_resume_and_unpublish_preserve_published_version_history(): void
    {
        [$owner, $form, $draft] = $this->makeForm('owner');
        $publishing = app(FormPublishingService::class);
        $version = $publishing->publishDraft($form, $draft->id, app(FormSchemaService::class));

        $publishing->pause($form->fresh());
        $this->assertSame('paused', $form->fresh()->status);
        $this->assertSame($version->id, $form->fresh()->published_version_id);

        $publishing->resume($form->fresh());
        $this->assertSame('published', $form->fresh()->status);

        $publishing->unpublish($form->fresh());
        $this->assertSame('draft', $form->fresh()->status);
        $this->assertNull($form->fresh()->published_version_id);
        $this->assertTrue($version->fresh()->is_published);
        $this->assertSame(1, $form->versions()->count());
    }

    public function test_active_published_version_must_belong_to_its_form(): void
    {
        [$owner, $form] = $this->makeForm('owner');
        [, $otherForm, $otherVersion] = $this->makeForm('owner');

        $this->expectException(QueryException::class);
        $form->forceFill(['published_version_id' => $otherVersion->id])->save();
    }

    /** @return array{User, Form, FormVersion} */
    private function makeForm(string $role): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
        $form = Form::factory()->create(['workspace_id' => $workspace->id]);
        $version = $form->versions()->create([
            'version_number' => 1,
            'schema' => app(FormSchemaService::class)->empty(),
            'is_published' => false,
        ]);

        return [$user, $form, $version];
    }
}
