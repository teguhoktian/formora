<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLog> */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'actor_id' => User::factory(),
            'event' => 'form.created',
            'subject_type' => 'form',
            'subject_id' => fake()->uuid(),
        ];
    }
}
