<?php

namespace App\Services;

use App\Models\FormSubmission;

class SubmissionResponsePresenter
{
    /** @return array<string, mixed> */
    public function present(FormSubmission $submission): array
    {
        $fields = $this->fields($submission->formVersion->schema);
        $answers = $submission->response_data;
        $result = [];

        foreach ($fields as $name => $field) {
            if (! array_key_exists($name, $answers)) {
                continue;
            }
            $value = $answers[$name];
            $result[$field['label']] = $this->isSensitive($name, $field['label'])
                ? '••••••••'
                : (is_array($value) && isset($value['private_path']) ? 'File terlampir' : $value);
        }

        return $result;
    }

    /** @param array<string, mixed> $schema
     * @return array<string, array{name: string, label: string}>
     */
    public function fields(array $schema): array
    {
        $fields = [];
        foreach ($schema['pages'] ?? [] as $page) {
            foreach ($page['fields'] ?? [] as $field) {
                if (isset($field['name'], $field['label']) && is_string($field['name']) && is_string($field['label'])) {
                    $fields[$field['name']] = ['name' => $field['name'], 'label' => $field['label']];
                }
            }
        }

        return $fields;
    }

    public function mask(string $name, string $label, mixed $value): mixed
    {
        if ($this->isSensitive($name, $label)) {
            return '••••••••';
        }
        if (is_array($value) && isset($value['private_path'])) {
            return 'File terlampir';
        }

        return is_array($value) ? implode(', ', array_map('strval', $value)) : $value;
    }

    private function isSensitive(string $name, string $label): bool
    {
        return (bool) preg_match('/nik|npwp|rekening|account|password|token|secret|kata\s+sandi/i', $name.' '.$label);
    }
}
