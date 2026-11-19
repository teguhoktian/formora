<?php

namespace Tests\Feature;

use App\Livewire\FormBuilder;
use App\Models\Form;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\FormSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_creation_uses_active_workspace_and_creates_initial_schema_atomically(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => 'owner']);

        $response = $this->actingAs($owner)->post(route('forms.store'), [
            'title' => 'Form onboarding',
            'workspace_id' => $otherWorkspace->id,
        ]);

        $form = Form::query()->where('title', 'Form onboarding')->firstOrFail();
        $response->assertRedirect(route('forms.edit', $form));
        $this->assertSame($workspace->id, $form->workspace_id);
        $this->assertSame(1, $form->versions()->count());
        $this->assertSame(1, $form->versions()->firstOrFail()->schema['schema_version']);
        $this->assertDatabaseMissing('forms', ['workspace_id' => $otherWorkspace->id, 'title' => 'Form onboarding']);
    }

    public function test_owner_can_add_and_autosave_a_field(): void
    {
        [$owner, $form] = $this->makeFormFor('owner');

        Livewire::actingAs($owner)
            ->test(FormBuilder::class, ['formId' => $form->id])
            ->call('addField', 'email')
            ->assertSet('saveState', 'saved')
            ->set('fieldLabel', 'Email kantor');

        $schema = $form->versions()->firstOrFail()->fresh()->schema;
        $this->assertSame('Email kantor', $schema['pages'][0]['fields'][0]['label']);
        $this->assertSame('email', $schema['pages'][0]['fields'][0]['type']);
    }

    public function test_builder_reorders_fields_using_stable_ids(): void
    {
        [$owner, $form] = $this->makeFormFor('owner');
        $schemas = app(FormSchemaService::class);
        $schema = $schemas->empty();
        $first = $schemas->newField('text', $schema);
        $second = $schemas->newField('date', $schema);
        $schema['pages'][0]['fields'] = [$first, $second];
        $form->versions()->firstOrFail()->update(['schema' => $schema]);

        Livewire::actingAs($owner)
            ->test(FormBuilder::class, ['formId' => $form->id])
            ->call('reorderFields', $schema['pages'][0]['id'], [$second['id'], $first['id']]);

        $saved = $form->versions()->firstOrFail()->fresh()->schema;
        $this->assertSame([$second['id'], $first['id']], array_column($saved['pages'][0]['fields'], 'id'));
    }

    public function test_builder_creates_a_new_draft_instead_of_changing_a_published_version(): void
    {
        [$owner, $form] = $this->makeFormFor('owner');
        $published = $form->versions()->firstOrFail();
        $published->update(['is_published' => true, 'published_at' => now()]);
        $publishedSchema = $published->schema;

        Livewire::actingAs($owner)
            ->test(FormBuilder::class, ['formId' => $form->id])
            ->call('addField', 'text');

        $this->assertEquals($publishedSchema, $published->fresh()->schema);
        $draft = $form->versions()->where('is_published', false)->firstOrFail();
        $this->assertSame(2, $draft->version_number);
        $this->assertCount(1, $draft->schema['pages'][0]['fields']);
    }

    public function test_builder_can_add_remove_pages_and_duplicate_fields(): void
    {
        [$owner, $form] = $this->makeFormFor('owner');
        $component = Livewire::actingAs($owner)->test(FormBuilder::class, ['formId' => $form->id]);

        $component->call('addPage');
        $pageId = $component->get('selectedPageId');
        $component->call('addField', 'text');
        $schema = $form->versions()->firstOrFail()->fresh()->schema;
        $fieldId = $schema['pages'][1]['fields'][0]['id'];

        $component->call('duplicateField', $fieldId);
        $schema = $form->versions()->firstOrFail()->fresh()->schema;
        $this->assertCount(2, $schema['pages'][1]['fields']);
        $this->assertNotSame($schema['pages'][1]['fields'][0]['id'], $schema['pages'][1]['fields'][1]['id']);

        $copyId = $component->get('selectedFieldId');
        $component->call('deleteField', $copyId)->call('deletePage', $pageId);

        $schema = $form->versions()->firstOrFail()->fresh()->schema;
        $this->assertCount(1, $schema['pages']);
    }

    public function test_builder_can_set_a_visibility_condition_for_a_field(): void
    {
        [$owner, $form] = $this->makeFormFor('owner');
        $schemas = app(FormSchemaService::class);
        $schema = $form->versions()->firstOrFail()->schema;
        $source = $schemas->newField('select', $schema);
        $target = $schemas->newField('text', $schema);
        $schema['pages'][0]['fields'] = [$source, $target];
        $form->versions()->firstOrFail()->update(['schema' => $schema]);

        Livewire::actingAs($owner)
            ->test(FormBuilder::class, ['formId' => $form->id])
            ->call('setTab', 'logic')
            ->call('selectField', $target['id'])
            ->set('visibilityFieldId', $source['id'])
            ->set('visibilityValue', 'pilihan_1')
            ->call('saveVisibilityCondition')
            ->assertSet('saveState', 'saved');

        $saved = $form->versions()->firstOrFail()->fresh()->schema;
        $this->assertSame([
            'field_id' => $source['id'],
            'operator' => 'equals',
            'value' => 'pilihan_1',
        ], $saved['pages'][0]['fields'][1]['visibility']);
    }

    public function test_builder_loads_each_fields_own_visibility_condition_when_switching_targets(): void
    {
        [$owner, $form] = $this->makeFormFor('owner');
        $schemas = app(FormSchemaService::class);
        $schema = $form->versions()->firstOrFail()->schema;
        $firstSource = $schemas->newField('select', $schema);
        $secondSource = $schemas->newField('radio', $schema);
        $firstTarget = $schemas->newField('text', $schema);
        $secondTarget = $schemas->newField('text', $schema);
        $firstTarget['visibility'] = [
            'field_id' => $firstSource['id'],
            'operator' => 'equals',
            'value' => 'pilihan_1',
        ];
        $secondTarget['visibility'] = [
            'field_id' => $secondSource['id'],
            'operator' => 'equals',
            'value' => 'pilihan_2',
        ];
        $schema['pages'][0]['fields'] = [$firstSource, $secondSource, $firstTarget, $secondTarget];
        $form->versions()->firstOrFail()->update(['schema' => $schema]);

        Livewire::actingAs($owner)
            ->test(FormBuilder::class, ['formId' => $form->id])
            ->call('setTab', 'logic')
            ->call('selectField', $firstTarget['id'])
            ->assertSet('visibilityFieldId', $firstSource['id'])
            ->assertSet('visibilityValue', 'pilihan_1')
            ->call('selectField', $secondTarget['id'])
            ->assertSet('visibilityFieldId', $secondSource['id'])
            ->assertSet('visibilityValue', 'pilihan_2');
    }

    public function test_builder_saves_type_specific_field_validation(): void
    {
        [$owner, $form] = $this->makeFormFor('owner');
        $schemas = app(FormSchemaService::class);
        $schema = $form->versions()->firstOrFail()->schema;
        $field = $schemas->newField('text', $schema);
        $schema['pages'][0]['fields'][] = $field;
        $form->versions()->firstOrFail()->update(['schema' => $schema]);

        Livewire::actingAs($owner)
            ->test(FormBuilder::class, ['formId' => $form->id])
            ->call('selectField', $field['id'])
            ->set('fieldValidation', ['min_length' => 3, 'custom_message' => 'Jawaban terlalu singkat.'])
            ->call('saveFieldValidation')
            ->assertSet('saveState', 'saved');

        $savedField = $form->versions()->firstOrFail()->fresh()->schema['pages'][0]['fields'][0];
        $this->assertSame(['min_length' => 3, 'custom_message' => 'Jawaban terlalu singkat.'], $savedField['validation']);
    }

    public function test_builder_can_edit_form_description_and_save_selected_file_types(): void
    {
        [$owner, $form] = $this->makeFormFor('owner');
        $schemas = app(FormSchemaService::class);
        $schema = $form->versions()->firstOrFail()->schema;
        $field = $schemas->newField('file', $schema);
        $schema['pages'][0]['fields'][] = $field;
        $form->versions()->firstOrFail()->update(['schema' => $schema]);

        $component = Livewire::actingAs($owner)->test(FormBuilder::class, ['formId' => $form->id])
            ->set('formDescription', 'Deskripsi yang tampil pada form publik.');
        $component->assertSet('saveState', 'saved');
        $this->assertSame('Deskripsi yang tampil pada form publik.', $form->fresh()->description);

        $component->set('selectedFileTypes', ['jpg'])->call('saveFieldValidation')->assertSet('saveState', 'saved');
        $savedField = $form->versions()->firstOrFail()->fresh()->schema['pages'][0]['fields'][0];
        $this->assertSame(['jpg'], $savedField['validation']['allowed_types']);
    }

    public function test_builder_can_configure_rating_scale(): void
    {
        [$owner, $form] = $this->makeFormFor('owner');
        $schemas = app(FormSchemaService::class);
        $schema = $form->versions()->firstOrFail()->schema;
        $field = $schemas->newField('rating', $schema);
        $schema['pages'][0]['fields'][] = $field;
        $form->versions()->firstOrFail()->update(['schema' => $schema]);

        Livewire::actingAs($owner)
            ->test(FormBuilder::class, ['formId' => $form->id])
            ->set('fieldSettings.scale', '8')
            ->call('saveFieldPresentation')
            ->assertSet('saveState', 'saved');

        $saved = $form->versions()->firstOrFail()->fresh()->schema['pages'][0]['fields'][0];
        $this->assertSame(8, $saved['settings']['scale']);
    }

    public function test_viewer_cannot_open_or_mutate_the_builder(): void
    {
        [$viewer, $form] = $this->makeFormFor('viewer');

        $response = $this->actingAs($viewer)->get(route('forms.edit', $form));

        $response->assertForbidden();
    }

    public function test_viewer_cannot_create_a_form(): void
    {
        [$viewer] = $this->makeFormFor('viewer');

        $this->actingAs($viewer)
            ->post(route('forms.store'), ['title' => 'Tidak diizinkan'])
            ->assertForbidden();
    }

    public function test_member_cannot_open_a_form_from_another_workspace(): void
    {
        [$member] = $this->makeFormFor('member');
        $foreignWorkspace = Workspace::factory()->create();
        $foreignForm = Form::factory()->create(['workspace_id' => $foreignWorkspace->id]);

        $response = $this->actingAs($member)->get(route('forms.edit', $foreignForm));

        $response->assertNotFound();
    }

    /** @return array{User, Form} */
    private function makeFormFor(string $role): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        WorkspaceMember::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
        $form = Form::factory()->create(['workspace_id' => $workspace->id]);
        $form->versions()->create([
            'version_number' => 1,
            'schema' => app(FormSchemaService::class)->empty(),
            'is_published' => false,
        ]);

        return [$user, $form];
    }
}
