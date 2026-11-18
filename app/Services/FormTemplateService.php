<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormTemplate;
use App\Models\FormTemplateVersion;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormTemplateService
{
    /** @param array{title: string, description: string|null, category: string} $details */
    public function saveFromForm(Workspace $workspace, User $user, Form $form, array $details, FormSchemaService $schemas): FormTemplate
    {
        $sourceVersion = $form->versions()->orderByDesc('version_number')->firstOrFail();
        $schema = $schemas->validate($sourceVersion->schema);

        return DB::transaction(function () use ($workspace, $user, $details, $schema): FormTemplate {
            $template = FormTemplate::query()->create([
                'workspace_id' => $workspace->id,
                'created_by' => $user->id,
                'kind' => 'workspace',
                'title' => $details['title'],
                'slug' => Str::lower(Str::random(40)),
                'description' => $details['description'],
                'category' => $details['category'],
                'visibility' => 'private',
                'status' => 'draft',
            ]);
            $template->versions()->create([
                'created_by' => $user->id,
                'version_number' => 1,
                'schema' => $schema,
            ]);

            return $template;
        });
    }

    /** @param array<string, mixed> $schema */
    public function createVersion(FormTemplate $template, User $user, array $schema, FormSchemaService $schemas): FormTemplateVersion
    {
        $validatedSchema = $schemas->validate($schema);

        return DB::transaction(function () use ($template, $user, $validatedSchema): FormTemplateVersion {
            $lockedTemplate = FormTemplate::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();
            $versionNumber = ((int) $lockedTemplate->versions()->max('version_number')) + 1;
            $version = $lockedTemplate->versions()->create([
                'created_by' => $user->id,
                'version_number' => $versionNumber,
                'schema' => $validatedSchema,
            ]);

            $lockedTemplate->update([
                'status' => $lockedTemplate->visibility === 'public' ? 'pending' : 'published',
                'moderated_by' => null,
                'moderation_note' => null,
                'moderated_at' => null,
            ]);

            return $version;
        });
    }

    public function useTemplate(FormTemplate $template, Workspace $workspace, FormSchemaService $schemas): Form
    {
        $version = $template->latestVersion()->firstOrFail();
        $schema = $schemas->validate($version->schema);
        foreach ($schema['pages'] as &$page) {
            $page['id'] = (string) Str::uuid();
            foreach ($page['fields'] as &$field) {
                $field['id'] = (string) Str::uuid();
            }
            unset($field);
        }
        unset($page);

        return DB::transaction(function () use ($template, $workspace, $schema): Form {
            $form = $workspace->forms()->create([
                'title' => $template->title,
                'description' => $template->description,
                'status' => 'draft',
            ]);
            $form->versions()->create([
                'version_number' => 1,
                'schema' => $schema,
                'is_published' => false,
            ]);

            return $form;
        });
    }
}
