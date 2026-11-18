<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Form> */
class FormFactory extends Factory
{
    protected $model = Form::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'title' => fake()->sentence(3),
            'description' => null,
            'status' => 'draft',
        ];
    }
}
