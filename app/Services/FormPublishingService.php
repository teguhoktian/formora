<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FormPublishingService
{
    public function publishDraft(Form $form, string $expectedDraftId, FormSchemaService $schemas): FormVersion
    {
        return DB::transaction(function () use ($form, $expectedDraftId, $schemas): FormVersion {
            $lockedForm = Form::query()->whereKey($form->id)->lockForUpdate()->firstOrFail();
            $draft = $lockedForm->versions()
                ->where('is_published', false)
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->first();

            if ($draft === null || $draft->id !== $expectedDraftId) {
                throw ValidationException::withMessages(['publish' => 'Tidak ada draf baru untuk dipublikasikan.']);
            }

            $draft->schema = $schemas->validate($draft->schema);
            $draft->is_published = true;
            $draft->setAttribute('published_at', now()->toDateTimeString());
            $draft->save();

            $slug = $lockedForm->slug;
            if ($slug === null) {
                $prefix = trim(Str::substr(Str::slug($lockedForm->title) ?: 'form', 0, 140), '-');
                $slug = $prefix.'-'.Str::lower(str_replace('-', '', $lockedForm->id));
            }

            $lockedForm->forceFill([
                'status' => 'published',
                'published_version_id' => $draft->id,
                'slug' => $slug,
            ])->save();

            return $draft;
        });
    }

    public function pause(Form $form): void
    {
        DB::transaction(function () use ($form): void {
            $lockedForm = Form::query()->whereKey($form->id)->lockForUpdate()->firstOrFail();
            if ($lockedForm->published_version_id === null || $lockedForm->status !== 'published') {
                throw ValidationException::withMessages(['publish' => 'Hanya form yang sedang dipublikasikan yang dapat dijeda.']);
            }

            $lockedForm->update(['status' => 'paused']);
        });
    }

    public function resume(Form $form): void
    {
        DB::transaction(function () use ($form): void {
            $lockedForm = Form::query()->whereKey($form->id)->lockForUpdate()->firstOrFail();
            if ($lockedForm->published_version_id === null || $lockedForm->status !== 'paused') {
                throw ValidationException::withMessages(['publish' => 'Tidak ada versi publik yang dapat dilanjutkan.']);
            }

            $lockedForm->update(['status' => 'published']);
        });
    }

    public function unpublish(Form $form): void
    {
        DB::transaction(function () use ($form): void {
            $lockedForm = Form::query()->whereKey($form->id)->lockForUpdate()->firstOrFail();
            if ($lockedForm->published_version_id === null) {
                throw ValidationException::withMessages(['publish' => 'Form ini belum memiliki versi publik.']);
            }

            $lockedForm->forceFill([
                'status' => 'draft',
                'published_version_id' => null,
            ])->save();
        });
    }
}
