<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Workspace;
use App\Services\FormPublishingService;
use App\Services\FormSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_only_see_the_active_published_schema(): void
    {
        [$form, $published] = $this->makePublishedForm([
            ['type' => 'text', 'name' => 'name', 'label' => 'Nama responden', 'required' => true],
        ]);
        $schema = $published->schema;
        $schema['pages'][0]['fields'][0]['label'] = 'Rahasia draft';
        $form->versions()->create([
            'version_number' => 2,
            'schema' => $schema,
            'is_published' => false,
        ]);

        $response = $this->get(route('public.forms.show', $form->slug));

        $response->assertOk()->assertSee('Nama responden')->assertDontSee('Rahasia draft');
        $this->assertGuest();
    }

    public function test_public_submission_uses_server_form_workspace_and_published_version(): void
    {
        [$form, $version] = $this->makePublishedForm([
            ['type' => 'text', 'name' => 'name', 'label' => 'Nama', 'required' => true],
        ]);
        $otherWorkspace = Workspace::factory()->create();

        $response = $this->post(route('public.forms.submit', $form->slug), [
            'answers' => ['name' => 'Dewi'],
            'workspace_id' => $otherWorkspace->id,
            'form_id' => 'forged-form-id',
            'form_version_id' => 'forged-version-id',
        ]);

        $submission = $form->submissions()->firstOrFail();
        $response->assertRedirect(route('public.forms.show', $form->slug));
        $this->assertSame($form->workspace_id, $submission->workspace_id);
        $this->assertSame($form->id, $submission->form_id);
        $this->assertSame($version->id, $submission->form_version_id);
        $this->assertSame(['name' => 'Dewi'], $submission->response_data);
        $this->assertSame('new', $submission->status);
        $this->assertNotEmpty($submission->reference_number);
        $this->assertSame($submission->reference_number, session('submission_reference'));
        $this->get('/f/'.$form->slug.'/submissions/'.$submission->reference_number)->assertNotFound();

        $this->post(route('public.forms.submit', $form->slug), ['answers' => ['name' => 'Dewi']])->assertRedirect();
        $this->assertSame(2, $form->submissions()->distinct()->count('reference_number'));
    }

    public function test_multi_page_form_renders_and_accepts_answers_from_each_page(): void
    {
        [$form] = $this->makePublishedForm([
            ['type' => 'text', 'name' => 'name', 'label' => 'Nama', 'required' => true],
            ['type' => 'date', 'name' => 'birth_date', 'label' => 'Tanggal lahir', 'required' => true, 'page' => 1],
        ]);

        $this->get(route('public.forms.show', $form->slug))
            ->assertOk()
            ->assertSee('Nama')
            ->assertSee('Tanggal lahir')
            ->assertSee('data-page-next', false);

        $this->from(route('public.forms.show', $form->slug))
            ->post(route('public.forms.submit', $form->slug), [
                'current_page' => 1,
                'answers' => ['birth_date' => '1990-04-22'],
            ])
            ->assertSessionHasErrors('answers.name')
            ->assertSessionHas('failed_page', 0);

        $this->post(route('public.forms.submit', $form->slug), [
            'answers' => ['name' => 'Dewi', 'birth_date' => '1990-04-22'],
        ])->assertRedirect();

        $this->assertSame([
            'name' => 'Dewi',
            'birth_date' => '1990-04-22',
        ], $form->submissions()->firstOrFail()->response_data);
    }

    public function test_invalid_required_email_and_unknown_answer_fields_are_rejected_without_flashing_input(): void
    {
        [$form] = $this->makePublishedForm([
            ['type' => 'email', 'name' => 'email', 'label' => 'Alamat email', 'required' => true],
        ]);

        $response = $this->from(route('public.forms.show', $form->slug))
            ->post(route('public.forms.submit', $form->slug), [
                'answers' => ['email' => 'bukan-email', 'forged' => 'nilai sensitif'],
            ]);

        $response->assertRedirect(route('public.forms.show', $form->slug))
            ->assertSessionHasErrors(['answers.email', 'answers']);
        $response->assertSessionMissing('_old_input');
        $this->assertSame(0, $form->submissions()->count());
    }

    public function test_select_and_checkbox_values_must_come_from_published_options(): void
    {
        [$form, $version] = $this->makePublishedForm([
            ['type' => 'select', 'name' => 'region', 'label' => 'Wilayah', 'required' => true],
            ['type' => 'checkbox', 'name' => 'interests', 'label' => 'Minat', 'required' => false],
        ]);
        $schema = $version->schema;
        $schema['pages'][0]['fields'][0]['settings'] = ['options' => [
            ['label' => 'Jakarta', 'value' => 'jakarta'],
            ['label' => 'Bandung', 'value' => 'bandung'],
        ]];
        $schema['pages'][0]['fields'][1]['settings'] = ['options' => [
            ['label' => 'Baca', 'value' => 'baca'],
            ['label' => 'Olahraga', 'value' => 'olahraga'],
        ]];
        $newVersion = $form->versions()->create([
            'version_number' => 2,
            'schema' => $schema,
            'is_published' => false,
        ]);
        app(FormPublishingService::class)->publishDraft($form, $newVersion->id, app(FormSchemaService::class));

        $response = $this->from(route('public.forms.show', $form->slug))
            ->post(route('public.forms.submit', $form->slug), [
                'answers' => ['region' => 'unknown', 'interests' => ['admin']],
            ]);

        $response->assertSessionHasErrors(['answers.region', 'answers.interests.0']);
        $this->assertSame(0, $form->submissions()->count());
    }

    public function test_searchable_select_uses_one_select2_style_control(): void
    {
        [$form] = $this->makePublishedForm([
            ['type' => 'select', 'name' => 'region', 'label' => 'Wilayah', 'required' => true],
        ]);

        $this->get(route('public.forms.show', $form->slug))
            ->assertOk()
            ->assertSee('data-searchable-select', false)
            ->assertSee('trigger.className = \'flex w-full items-center justify-between', false)
            ->assertSee('dropdown.hidden = !dropdown.hidden', false)
            ->assertSee('select.classList.add(\'sr-only\')', false)
            ->assertDontSee("search.className = 'w-full rounded-xl border", false);
    }

    public function test_hidden_required_field_is_skipped_and_hidden_answer_is_not_stored(): void
    {
        [$form, $version] = $this->makePublishedForm([
            ['type' => 'select', 'name' => 'request_type', 'label' => 'Jenis permohonan', 'required' => true],
            ['type' => 'text', 'name' => 'company_name', 'label' => 'Nama perusahaan', 'required' => true],
        ]);
        $schema = $version->schema;
        $source = $schema['pages'][0]['fields'][0];
        $target = $schema['pages'][0]['fields'][1];
        $schema['pages'][0]['fields'][0]['settings']['options'] = [
            ['label' => 'Pribadi', 'value' => 'personal'],
            ['label' => 'Perusahaan', 'value' => 'company'],
        ];
        $schema['pages'][0]['fields'][1]['visibility'] = [
            'field_id' => $source['id'],
            'operator' => 'equals',
            'value' => 'company',
        ];
        $draft = $form->versions()->create(['version_number' => 2, 'schema' => $schema, 'is_published' => false]);
        $published = app(FormPublishingService::class)->publishDraft($form, $draft->id, app(FormSchemaService::class));

        $this->get(route('public.forms.show', $form->slug))
            ->assertOk()
            ->assertSee('data-visibility-source="'.$source['id'].'"', false);

        $this->post(route('public.forms.submit', $form->slug), [
            'answers' => ['request_type' => 'personal', 'company_name' => 'must-not-be-saved'],
        ])->assertRedirect();

        $submission = $form->submissions()->firstOrFail();
        $this->assertSame(['request_type' => 'personal'], $submission->response_data);

        $this->from(route('public.forms.show', $form->slug))
            ->post(route('public.forms.submit', $form->slug), ['answers' => ['request_type' => 'company']])
            ->assertSessionHasErrors('answers.company_name');
        $this->assertSame(1, $form->submissions()->count());
        $this->assertNotNull($published->fresh());
    }

    public function test_paused_and_unpublished_forms_are_not_publicly_accessible(): void
    {
        [$pausedForm] = $this->makePublishedForm([
            ['type' => 'text', 'name' => 'name', 'label' => 'Nama', 'required' => false],
        ]);
        app(FormPublishingService::class)->pause($pausedForm);
        $this->get(route('public.forms.show', $pausedForm->slug))->assertNotFound();

        [$unpublishedForm] = $this->makePublishedForm([
            ['type' => 'text', 'name' => 'name', 'label' => 'Nama', 'required' => false],
        ]);
        app(FormPublishingService::class)->unpublish($unpublishedForm);
        $this->get(route('public.forms.show', $unpublishedForm->slug))->assertNotFound();
    }

    public function test_file_upload_is_validated_and_saved_on_private_disk(): void
    {
        Storage::fake('local');
        [$form] = $this->makePublishedForm([
            ['type' => 'file', 'name' => 'document', 'label' => 'Dokumen', 'required' => true],
        ]);

        $this->post(route('public.forms.submit', $form->slug), [
            'answers' => ['document' => UploadedFile::fake()->image('dokumen.jpg')],
        ])->assertRedirect(route('public.forms.show', $form->slug));

        $submission = $form->submissions()->firstOrFail();
        $path = $submission->response_data['document']['private_path'];
        $this->assertSame('dokumen.jpg', $submission->response_data['document']['original_name']);
        $this->assertStringStartsWith('form-submissions/'.$form->id.'/', $path);
        Storage::disk('local')->assertExists($path);

        $this->from(route('public.forms.show', $form->slug))
            ->post(route('public.forms.submit', $form->slug), [
                'answers' => ['document' => UploadedFile::fake()->create('bad.exe', 20, 'application/x-msdownload')],
            ])
            ->assertSessionHasErrors('answers.document');
        $this->assertSame(1, $form->submissions()->count());
    }

    public function test_configured_text_and_number_rules_are_checked_on_the_server(): void
    {
        [$textForm] = $this->makePublishedForm([
            ['type' => 'text', 'name' => 'code', 'label' => 'Kode', 'required' => true, 'validation' => [
                'min_length' => 4, 'max_length' => 8, 'contains' => 'FORM', 'pattern' => '[A-Z]{4}[0-9]{2}', 'pattern_mode' => 'matches', 'custom_message' => 'Kode belum sesuai.',
            ]],
        ]);
        $this->from(route('public.forms.show', $textForm->slug))
            ->post(route('public.forms.submit', $textForm->slug), ['answers' => ['code' => 'abc']])
            ->assertSessionHasErrors('answers.code');
        $this->assertSame('Kode belum sesuai.', session('errors')->getBag('default')->first('answers.code'));
        $this->assertSame(0, $textForm->submissions()->count());
        $this->post(route('public.forms.submit', $textForm->slug), ['answers' => ['code' => 'FORM42']])->assertRedirect();
        $this->assertSame(1, $textForm->submissions()->count());

        [$numberForm] = $this->makePublishedForm([
            ['type' => 'number', 'name' => 'amount', 'label' => 'Jumlah', 'required' => true, 'validation' => [
                'operator' => 'between', 'min' => 2, 'max' => 4,
            ]],
        ]);
        $this->from(route('public.forms.show', $numberForm->slug))
            ->post(route('public.forms.submit', $numberForm->slug), ['answers' => ['amount' => '5']])
            ->assertSessionHasErrors('answers.amount');
        $this->post(route('public.forms.submit', $numberForm->slug), ['answers' => ['amount' => '3']])->assertRedirect();
        $this->assertSame(1, $numberForm->submissions()->count());
    }

    public function test_checkbox_count_validation_is_enforced(): void
    {
        [$form] = $this->makePublishedForm([
            ['type' => 'checkbox', 'name' => 'topics', 'label' => 'Topik', 'required' => false, 'validation' => [
                'exact_selections' => 2,
            ]],
        ]);
        $this->from(route('public.forms.show', $form->slug))
            ->post(route('public.forms.submit', $form->slug), ['answers' => ['topics' => ['pilihan_1']]])
            ->assertSessionHasErrors('answers.topics');
        $this->post(route('public.forms.submit', $form->slug), ['answers' => ['topics' => ['pilihan_1', 'pilihan_2']]])->assertRedirect();
        $this->assertSame(1, $form->submissions()->count());
    }

    public function test_configured_multiple_private_files_obey_type_count_and_size_limits(): void
    {
        Storage::fake('local');
        [$form] = $this->makePublishedForm([
            ['type' => 'file', 'name' => 'documents', 'label' => 'Dokumen', 'required' => true, 'validation' => [
                'allowed_types' => ['pdf'], 'max_files' => 2, 'max_size_kb' => 64,
            ]],
        ]);
        $this->post(route('public.forms.submit', $form->slug), [
            'answers' => ['documents' => [UploadedFile::fake()->create('one.pdf', 32), UploadedFile::fake()->create('two.pdf', 32)]],
        ])->assertRedirect();
        $stored = $form->submissions()->firstOrFail()->response_data['documents'];
        $this->assertCount(2, $stored);
        foreach ($stored as $file) {
            Storage::disk('local')->assertExists($file['private_path']);
        }
        $this->from(route('public.forms.show', $form->slug))
            ->post(route('public.forms.submit', $form->slug), [
                'answers' => ['documents' => [UploadedFile::fake()->create('one.pdf', 32), UploadedFile::fake()->create('two.pdf', 32), UploadedFile::fake()->create('three.pdf', 32)]],
            ])->assertSessionHasErrors('answers.documents');
        $this->assertSame(1, $form->submissions()->count());
    }

    public function test_rating_slider_and_date_modes_render_and_validate(): void
    {
        [$form] = $this->makePublishedForm([
            ['type' => 'rating', 'name' => 'rating', 'label' => 'Penilaian', 'required' => true],
            ['type' => 'slider', 'name' => 'score', 'label' => 'Skor', 'required' => true, 'settings' => ['min' => 0, 'max' => 10, 'step' => 2]],
            ['type' => 'date', 'name' => 'meeting', 'label' => 'Waktu rapat', 'required' => true, 'settings' => ['mode' => 'datetime']],
            ['type' => 'date', 'name' => 'period', 'label' => 'Periode', 'required' => true, 'settings' => ['mode' => 'date_range']],
        ]);

        $this->get(route('public.forms.show', $form->slug))
            ->assertOk()
            ->assertSee('data-rating', false)
            ->assertSee('data-number-slider', false)
            ->assertSee('datetime-local', false)
            ->assertSee('answers[period][start]', false);

        $answers = [
            'rating' => '4',
            'score' => '6',
            'meeting' => '2026-11-19T09:30',
            'period' => ['start' => '2026-11-01', 'end' => '2026-11-19'],
        ];
        $this->post(route('public.forms.submit', $form->slug), ['answers' => $answers])->assertRedirect();
        $this->assertSame($answers['period'], $form->submissions()->firstOrFail()->response_data['period']);

        $answers['score'] = '3';
        $answers['period'] = ['start' => '2026-11-20', 'end' => '2026-11-19'];
        $this->from(route('public.forms.show', $form->slug))
            ->post(route('public.forms.submit', $form->slug), ['answers' => $answers])
            ->assertSessionHasErrors(['answers.score', 'answers.period']);
        $this->assertSame(1, $form->submissions()->count());
    }

    public function test_public_submit_route_is_rate_limited(): void
    {
        [$form] = $this->makePublishedForm([
            ['type' => 'text', 'name' => 'name', 'label' => 'Nama', 'required' => true],
        ]);
        $url = route('public.forms.submit', $form->slug);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post($url, ['website' => 'bot'])->assertRedirect();
        }

        $this->post($url, ['website' => 'bot'])->assertTooManyRequests();
    }

    /** @param list<array{type: string, name: string, label: string, required: bool, page?: int, validation?: array<string, mixed>, settings?: array<string, mixed>}> $fieldDefinitions
     * @return array{Form, FormVersion}
     */
    private function makePublishedForm(array $fieldDefinitions): array
    {
        $workspace = Workspace::factory()->create();
        $form = $workspace->forms()->create(['title' => 'Permohonan Publik', 'status' => 'draft']);
        $schemas = app(FormSchemaService::class);
        $schema = $schemas->empty();
        foreach ($fieldDefinitions as $definition) {
            $pageIndex = $definition['page'] ?? 0;
            while (count($schema['pages']) <= $pageIndex) {
                $schema['pages'][] = $schemas->newPage('Halaman '.(count($schema['pages']) + 1));
            }
            $field = $schemas->newField($definition['type'], $schema);
            $field['name'] = $definition['name'];
            $field['label'] = $definition['label'];
            $field['required'] = $definition['required'];
            $field['validation'] = $definition['validation'] ?? [];
            $field['settings'] = $definition['settings'] ?? $field['settings'];
            $schema['pages'][$pageIndex]['fields'][] = $field;
        }
        $draft = $form->versions()->create([
            'version_number' => 1,
            'schema' => $schema,
            'is_published' => false,
        ]);

        $published = app(FormPublishingService::class)->publishDraft($form, $draft->id, $schemas);

        return [$form->fresh(), $published];
    }
}
