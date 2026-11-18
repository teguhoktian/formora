<?php

namespace App\Models;

use Database\Factories\WorkspaceMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $workspace_id
 * @property int $user_id
 * @property string $role
 */
#[Fillable(['workspace_id', 'user_id', 'role'])]
class WorkspaceMember extends Model
{
    /** @use HasFactory<WorkspaceMemberFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['user_id' => 'integer'];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
