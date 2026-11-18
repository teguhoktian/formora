<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $workspace_id
 * @property int|null $created_by
 * @property int|null $moderated_by
 * @property string $kind
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string $category
 * @property string $visibility
 * @property string $status
 * @property string|null $moderation_note
 * @property Carbon|null $moderated_at
 */
#[Fillable(['workspace_id', 'created_by', 'moderated_by', 'kind', 'title', 'slug', 'description', 'category', 'visibility', 'status', 'moderation_note', 'moderated_at'])]
class FormTemplate extends Model
{
    use HasUuids;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'moderated_by' => 'integer',
            'moderated_at' => 'datetime',
        ];
    }

    /** @return HasMany<FormTemplateVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(FormTemplateVersion::class, 'template_id');
    }

    /** @return HasOne<FormTemplateVersion, $this> */
    public function latestVersion(): HasOne
    {
        return $this->versions()->one()->orderByDesc('version_number');
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
