<?php

namespace App\Models;

use Database\Factories\FormVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $form_id
 * @property int $version_number
 * @property array<string, mixed> $schema
 * @property bool $is_published
 * @property Carbon|null $published_at
 */
#[Fillable(['form_id', 'version_number', 'schema', 'is_published', 'published_at'])]
class FormVersion extends Model
{
    /** @use HasFactory<FormVersionFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Form, $this> */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** @return HasMany<FormSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
