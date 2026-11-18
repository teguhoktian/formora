<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Support\Facades\DB;

class FormBuilderService
{
    /** @return array<string, mixed> */
    public function ensureDraft(Form $form, FormSchemaService $schemas): array
    {
        return DB::transaction(function () use ($form, $schemas): array {
            $lockedForm = Form::query()->whereKey($form->id)->lockForUpdate()->firstOrFail();
            $draft = $lockedForm->versions()->where('is_published', false)->orderByDesc('version_number')->first();

            if ($draft === null) {
                $latest = $lockedForm->versions()->orderByDesc('version_number')->first();
                $nextVersion = $latest === null ? 1 : $latest->version_number + 1;
                $draft = $lockedForm->versions()->create([
                    'version_number' => $nextVersion,
                    'schema' => $latest === null ? $schemas->empty() : $latest->schema,
                    'is_published' => false,
                ]);
            }

            return $draft->schema;
        });
    }

    /** @param array<string, mixed> $schema */
    public function saveDraft(Form $form, array $schema, FormSchemaService $schemas): FormVersion
    {
        $validatedSchema = $schemas->validate($schema);

        return DB::transaction(function () use ($form, $validatedSchema): FormVersion {
            $lockedForm = Form::query()->whereKey($form->id)->lockForUpdate()->firstOrFail();
            $draft = $lockedForm->versions()->where('is_published', false)->orderByDesc('version_number')->first();

            if ($draft === null) {
                $latest = $lockedForm->versions()->orderByDesc('version_number')->first();
                $draft = $lockedForm->versions()->create([
                    'version_number' => $latest === null ? 1 : $latest->version_number + 1,
                    'schema' => $validatedSchema,
                    'is_published' => false,
                ]);
            } else {
                $draft->update(['schema' => $validatedSchema]);
            }

            $lockedForm->touch();

            return $draft;
        });
    }
}
