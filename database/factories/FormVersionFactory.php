<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FormVersion> */
class FormVersionFactory extends Factory
{
    protected $model = FormVersion::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'version_number' => 1,
            'schema' => [
                'schema_version' => 1,
                'pages' => [],
                'settings' => [],
            ],
            'is_published' => false,
            'published_at' => null,
        ];
    }
}
