<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** @property array{status?: string|null, search?: string|null, from?: string|null, to?: string|null} $filters */
class SubmissionExport extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }
}
