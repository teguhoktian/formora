<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FormSubmission> */
class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'status' => 'new',
            'response_data' => [],
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (FormSubmission $submission): void {
            $form = $submission->form;

            $submission->workspace_id ??= $form->workspace_id;

            if (! $submission->form_version_id) {
                $submission->form_version_id = FormVersion::factory()
                    ->for($form)
                    ->create()
                    ->id;
            }
        });
    }
}
