<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property string $id
 * @property string $template_id
 * @property int|null $created_by
 * @property int $version_number
 * @property array<string, mixed> $schema
 */
#[Fillable(['template_id', 'created_by', 'version_number', 'schema'])]
class FormTemplateVersion extends Model
{
    use HasUuids;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Template versions are immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Template versions cannot be deleted.');
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['schema' => 'array'];
    }

    /** @return BelongsTo<FormTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'template_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
