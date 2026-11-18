<?php

namespace App\Models;

use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string|null $published_version_id
 */
#[Fillable(['workspace_id', 'title', 'description', 'status', 'published_version_id'])]
class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<FormVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class);
    }

    /** @return BelongsTo<FormVersion, $this> */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'published_version_id');
    }

    /** @return HasMany<FormSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
