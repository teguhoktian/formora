<?php

namespace App\Livewire;

use App\Models\Form;
use App\Services\CurrentWorkspace;
use App\Services\FormBuilderService;
use App\Services\FormPublishingService;
use App\Services\FormSchemaService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class FormBuilder extends Component
{
    #[Locked]
    public string $formId;

    public string $formTitle = '';

    public string $selectedPageId = '';

    public ?string $selectedFieldId = null;

    public string $activeTab = 'build';

    public string $fieldLabel = '';

    public string $fieldName = '';

    public bool $fieldRequired = false;

    public string $fieldHelpText = '';

    public string $optionLines = '';

    public string $pageTitle = '';

    public string $submitLabel = 'Kirim';

    public string $successMessage = 'Terima kasih, data berhasil dikirim.';

    public string $saveState = 'saved';

    public function mount(string $formId, FormBuilderService $builder, FormSchemaService $schemas): void
    {
        $this->formId = $formId;
        $form = $this->currentForm();
        Gate::authorize('update', $form);
        $this->formTitle = $form->title;

        $schema = $builder->ensureDraft($form, $schemas);
        $this->selectedPageId = $schema['pages'][0]['id'];
        $this->pageTitle = $schema['pages'][0]['title'];
        $this->submitLabel = $schema['settings']['submit_label'];
        $this->successMessage = $schema['settings']['success_message'];
        $this->selectFirstField($schema);
    }

    public function updatedFormTitle(string $value): void
    {
        $this->authorizeCurrentForm();
        $this->attemptSave(function () use ($value): void {
            $validated = validator(['title' => $value], ['title' => ['required', 'string', 'max:160']])->validate();
            $this->currentForm()->update(['title' => $validated['title']]);
        });
    }

    public function updatedFieldLabel(string $value): void
    {
        $this->updateSelectedField('label', $value);
    }

    public function updatedFieldName(string $value): void
    {
        $this->updateSelectedField('name', $value);
    }

    public function updatedFieldRequired(bool $value): void
    {
        $this->updateSelectedField('required', $value);
    }

    public function updatedFieldHelpText(string $value): void
    {
        $this->updateSelectedField('help_text', $value === '' ? null : $value);
    }

    public function updatedOptionLines(string $value): void
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $value) ?: []), fn (string $line): bool => $line !== ''));
        $options = [];
        $used = [];

        foreach ($lines as $label) {
            $base = Str::slug($label, '_');
            $base = preg_match('/^[a-z]/', $base) === 1 ? $base : 'pilihan_'.$base;
            $candidate = $base;
            $suffix = 2;
            while (isset($used[$candidate])) {
                $candidate = $base.'_'.$suffix++;
            }
            $used[$candidate] = true;
            $options[] = ['label' => $label, 'value' => $candidate];
        }

        $this->updateSelectedField('settings', ['options' => $options]);
    }

    public function updatedPageTitle(string $value): void
    {
        $this->attemptSave(function () use ($value): void {
            $validated = validator(['title' => $value], ['title' => ['required', 'string', 'max:120']])->validate();
            $schema = $this->schema();
            $pageIndex = $this->pageIndex($schema, $this->selectedPageId);
            $schema['pages'][$pageIndex]['title'] = $validated['title'];
            $this->saveSchema($schema);
        });
    }

    public function updatedSubmitLabel(string $value): void
    {
        $this->updateSettings('submit_label', $value, 80);
    }

    public function updatedSuccessMessage(string $value): void
    {
        $this->updateSettings('success_message', $value, 500);
    }

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, ['build', 'settings', 'preview'], true), 422);
        $this->activeTab = $tab;
    }

    public function publishDraft(string $expectedDraftId, FormPublishingService $publishing, FormSchemaService $schemas): void
    {
        $form = $this->currentForm();
        Gate::authorize('publish', $form);
        $publishing->publishDraft($form, $expectedDraftId, $schemas);
    }

    public function pause(FormPublishingService $publishing): void
    {
        $form = $this->currentForm();
        Gate::authorize('publish', $form);
        $publishing->pause($form);
    }

    public function resume(FormPublishingService $publishing): void
    {
        $form = $this->currentForm();
        Gate::authorize('publish', $form);
        $publishing->resume($form);
    }

    public function unpublish(FormPublishingService $publishing): void
    {
        $form = $this->currentForm();
        Gate::authorize('publish', $form);
        $publishing->unpublish($form);
    }

    public function addField(string $type, FormBuilderService $builder, FormSchemaService $schemas): void
    {
        $this->authorizeCurrentForm();
        $this->attemptSave(function () use ($type, $builder, $schemas): void {
            $schema = $this->schema();
            $pageIndex = $this->pageIndex($schema, $this->selectedPageId);
            $field = $schemas->newField($type, $schema);
            $schema['pages'][$pageIndex]['fields'][] = $field;
            $this->saveSchema($schema, $builder, $schemas);
            $this->selectedFieldId = $field['id'];
            $this->syncFieldDraft($field);
        });
    }

    public function selectPage(string $pageId): void
    {
        $schema = $this->schema();
        $pageIndex = $this->pageIndex($schema, $pageId);
        $this->selectedPageId = $pageId;
        $this->pageTitle = $schema['pages'][$pageIndex]['title'];
        $this->selectedFieldId = null;
        $this->selectFirstField($schema);
    }

    public function addPage(FormBuilderService $builder, FormSchemaService $schemas): void
    {
        $this->authorizeCurrentForm();
        $this->attemptSave(function () use ($builder, $schemas): void {
            $schema = $this->schema();
            abort_if(count($schema['pages']) >= 20, 422, 'Form maksimal memiliki 20 halaman.');
            $page = $schemas->newPage('Halaman '.(count($schema['pages']) + 1));
            $schema['pages'][] = $page;
            $this->saveSchema($schema, $builder, $schemas);
            $this->selectedPageId = $page['id'];
            $this->pageTitle = $page['title'];
            $this->selectedFieldId = null;
            $this->clearFieldDraft();
        });
    }

    public function deletePage(string $pageId, FormBuilderService $builder, FormSchemaService $schemas): void
    {
        $this->authorizeCurrentForm();
        $this->attemptSave(function () use ($pageId, $builder, $schemas): void {
            $schema = $this->schema();
            abort_if(count($schema['pages']) <= 1, 422, 'Form harus memiliki setidaknya satu halaman.');
            $pageIndex = $this->pageIndex($schema, $pageId);
            array_splice($schema['pages'], $pageIndex, 1);
            $this->saveSchema($schema, $builder, $schemas);
            $this->selectedPageId = $schema['pages'][0]['id'];
            $this->pageTitle = $schema['pages'][0]['title'];
            $this->selectFirstField($schema);
        });
    }

    public function selectField(string $fieldId): void
    {
        $schema = $this->schema();
        $field = $this->findField($schema, $this->selectedPageId, $fieldId);
        $this->selectedFieldId = $fieldId;
        $this->syncFieldDraft($field);
    }

    public function duplicateField(string $fieldId, FormBuilderService $builder, FormSchemaService $schemas): void
    {
        $this->authorizeCurrentForm();
        $this->attemptSave(function () use ($fieldId, $builder, $schemas): void {
            $schema = $this->schema();
            $pageIndex = $this->pageIndex($schema, $this->selectedPageId);
            $fields = $schema['pages'][$pageIndex]['fields'];
            $fieldIndex = $this->fieldIndex($fields, $fieldId);
            $copy = $fields[$fieldIndex];
            $copy['id'] = (string) Str::uuid();
            $copy['name'] = $schemas->uniqueName($schema, $copy['name'].'_salinan');
            array_splice($schema['pages'][$pageIndex]['fields'], $fieldIndex + 1, 0, [$copy]);
            $this->saveSchema($schema, $builder, $schemas);
            $this->selectedFieldId = $copy['id'];
            $this->syncFieldDraft($copy);
        });
    }

    public function deleteField(string $fieldId, FormBuilderService $builder, FormSchemaService $schemas): void
    {
        $this->authorizeCurrentForm();
        $this->attemptSave(function () use ($fieldId, $builder, $schemas): void {
            $schema = $this->schema();
            $pageIndex = $this->pageIndex($schema, $this->selectedPageId);
            $fields = $schema['pages'][$pageIndex]['fields'];
            $fieldIndex = $this->fieldIndex($fields, $fieldId);
            array_splice($schema['pages'][$pageIndex]['fields'], $fieldIndex, 1);
            $this->saveSchema($schema, $builder, $schemas);

            if ($this->selectedFieldId === $fieldId) {
                $this->selectedFieldId = $schema['pages'][$pageIndex]['fields'][0]['id'] ?? null;
                $field = $schema['pages'][$pageIndex]['fields'][0] ?? null;
                $field === null ? $this->clearFieldDraft() : $this->syncFieldDraft($field);
            }
        });
    }

    /** @param array<array-key, mixed> $orderedIds */
    public function reorderFields(string $pageId, array $orderedIds, FormBuilderService $builder, FormSchemaService $schemas): void
    {
        $this->authorizeCurrentForm();
        $this->attemptSave(function () use ($pageId, $orderedIds, $builder, $schemas): void {
            if (! array_is_list($orderedIds)) {
                abort(422, 'Urutan field tidak valid.');
            }
            $validatedOrder = [];
            foreach ($orderedIds as $id) {
                if (! is_string($id)) {
                    abort(422, 'Urutan field tidak valid.');
                }
                $validatedOrder[] = $id;
            }

            $schema = $this->schema();
            $pageIndex = $this->pageIndex($schema, $pageId);
            $fields = $schema['pages'][$pageIndex]['fields'];
            $existingIds = array_column($fields, 'id');
            if (count($validatedOrder) !== count($existingIds)
                || count(array_unique($validatedOrder)) !== count($existingIds)
                || array_diff($existingIds, $validatedOrder) !== []
                || array_diff($validatedOrder, $existingIds) !== []) {
                abort(422, 'Urutan field tidak valid.');
            }

            $byId = [];
            foreach ($fields as $field) {
                $byId[$field['id']] = $field;
            }
            $schema['pages'][$pageIndex]['fields'] = array_map(fn (string $id): array => $byId[$id], $validatedOrder);
            $this->saveSchema($schema, $builder, $schemas);
        });
    }

    public function render(FormBuilderService $builder, FormSchemaService $schemas): View
    {
        $form = $this->currentForm();
        Gate::authorize('update', $form);
        $schema = $builder->ensureDraft($form, $schemas);
        $draftId = $form->versions()->where('is_published', false)->orderByDesc('version_number')->value('id');
        $pageIndex = $this->pageIndex($schema, $this->selectedPageId);

        return view('livewire.form-builder', [
            'form' => $form,
            'schema' => $schema,
            'activePage' => $schema['pages'][$pageIndex],
            'selectedField' => $this->selectedField($schema),
            'fieldTypes' => FormSchemaService::FIELD_TYPES,
            'canPublish' => auth()->user()?->can('publish', $form) ?? false,
            'draftId' => $draftId,
        ]);
    }

    private function updateSelectedField(string $property, mixed $value): void
    {
        $this->authorizeCurrentForm();
        $this->attemptSave(function () use ($property, $value): void {
            if ($this->selectedFieldId === null) {
                return;
            }

            $schema = $this->schema();
            $pageIndex = $this->pageIndex($schema, $this->selectedPageId);
            $fieldIndex = $this->fieldIndex($schema['pages'][$pageIndex]['fields'], $this->selectedFieldId);
            if ($property === 'settings') {
                $schema['pages'][$pageIndex]['fields'][$fieldIndex]['settings'] = $value;
            } else {
                $schema['pages'][$pageIndex]['fields'][$fieldIndex][$property] = $value;
            }
            $this->saveSchema($schema);
        });
    }

    private function updateSettings(string $property, string $value, int $maxLength): void
    {
        $this->authorizeCurrentForm();
        $this->attemptSave(function () use ($property, $value, $maxLength): void {
            $validated = validator(['value' => $value], ['value' => ['required', 'string', 'max:'.$maxLength]])->validate();
            $schema = $this->schema();
            $schema['settings'][$property] = $validated['value'];
            $this->saveSchema($schema);
        });
    }

    /** @param array<string, mixed> $schema */
    private function saveSchema(array $schema, ?FormBuilderService $builder = null, ?FormSchemaService $schemas = null): void
    {
        ($builder ?? app(FormBuilderService::class))
            ->saveDraft($this->currentForm(), $schema, $schemas ?? app(FormSchemaService::class));
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return app(FormBuilderService::class)->ensureDraft($this->currentForm(), app(FormSchemaService::class));
    }

    private function attemptSave(callable $callback): void
    {
        $this->saveState = 'saving';
        try {
            $callback();
            $this->saveState = 'saved';
            $this->resetErrorBag('save');
        } catch (ValidationException $exception) {
            $this->saveState = 'error';
            throw $exception;
        } catch (HttpExceptionInterface $exception) {
            $this->saveState = 'error';
            throw $exception;
        } catch (Throwable $exception) {
            $this->saveState = 'error';
            Log::error('Form builder save failed.', ['form_id' => $this->formId]);
            $this->addError('save', 'Perubahan belum tersimpan. Silakan coba lagi.');
        }
    }

    private function currentForm(): Form
    {
        $user = auth()->user();
        abort_if($user === null, 403);
        $workspace = app(CurrentWorkspace::class)->get($user, request());
        abort_if($workspace === null, 404);

        return $workspace->forms()->whereKey($this->formId)->firstOrFail();
    }

    private function authorizeCurrentForm(): void
    {
        Gate::authorize('update', $this->currentForm());
    }

    /** @param array<string, mixed> $schema */
    private function pageIndex(array $schema, string $pageId): int
    {
        foreach ($schema['pages'] as $index => $page) {
            if ($page['id'] === $pageId) {
                return $index;
            }
        }

        abort(404);
    }

    /** @param array<int, array<string, mixed>> $fields */
    private function fieldIndex(array $fields, string $fieldId): int
    {
        foreach ($fields as $index => $field) {
            if ($field['id'] === $fieldId) {
                return $index;
            }
        }

        abort(404);
    }

    /** @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function findField(array $schema, string $pageId, string $fieldId): array
    {
        $pageIndex = $this->pageIndex($schema, $pageId);
        $fieldIndex = $this->fieldIndex($schema['pages'][$pageIndex]['fields'], $fieldId);

        return $schema['pages'][$pageIndex]['fields'][$fieldIndex];
    }

    /** @param array<string, mixed> $schema
     * @return array<string, mixed>|null
     */
    private function selectedField(array $schema): ?array
    {
        if ($this->selectedFieldId === null) {
            return null;
        }

        foreach ($schema['pages'] as $page) {
            if ($page['id'] !== $this->selectedPageId) {
                continue;
            }
            foreach ($page['fields'] as $field) {
                if ($field['id'] === $this->selectedFieldId) {
                    return $field;
                }
            }
        }

        return null;
    }

    /** @param array<string, mixed> $schema */
    private function selectFirstField(array $schema): void
    {
        $pageIndex = $this->pageIndex($schema, $this->selectedPageId);
        $field = $schema['pages'][$pageIndex]['fields'][0] ?? null;
        $this->selectedFieldId = $field['id'] ?? null;
        $field === null ? $this->clearFieldDraft() : $this->syncFieldDraft($field);
    }

    /** @param array<string, mixed> $field */
    private function syncFieldDraft(array $field): void
    {
        $this->fieldLabel = $field['label'];
        $this->fieldName = $field['name'];
        $this->fieldRequired = $field['required'];
        $this->fieldHelpText = $field['help_text'] ?? '';
        $this->optionLines = implode("\n", array_column($field['settings']['options'] ?? [], 'label'));
    }

    private function clearFieldDraft(): void
    {
        $this->fieldLabel = '';
        $this->fieldName = '';
        $this->fieldRequired = false;
        $this->fieldHelpText = '';
        $this->optionLines = '';
    }
}
