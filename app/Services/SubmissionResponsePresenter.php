<?php

namespace App\Services;

use App\Models\FormSubmission;

class SubmissionResponsePresenter
{
    public function __construct(private SmartFieldRegistry $smartFields) {}

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
            $result[$field['label']] = $this->isSensitive($name, $field['label'], $field['type'])
                ? '••••••••'
                : $this->presentValue($field['type'], $value);
        }

        return $result;
    }

    /** @param array<string, mixed> $schema
     * @return array<string, array{name: string, label: string, type: string}>
     */
    public function fields(array $schema): array
    {
        $fields = [];
        foreach ($schema['pages'] ?? [] as $page) {
            foreach ($page['fields'] ?? [] as $field) {
                if (isset($field['name'], $field['label'], $field['type']) && is_string($field['name']) && is_string($field['label']) && is_string($field['type'])) {
                    $fields[$field['name']] = ['name' => $field['name'], 'label' => $field['label'], 'type' => $field['type']];
                }
            }
        }

        return $fields;
    }

    public function mask(string $name, string $label, mixed $value, ?string $type = null): mixed
    {
        if ($this->isSensitive($name, $label, $type)) {
            return '••••••••';
        }
        if (is_array($value) && isset($value['private_path'])) {
            return 'File terlampir';
        }
        if ($this->isFileList($value)) {
            return 'File terlampir ('.count($value).' file)';
        }

        return $type !== null
            ? $this->smartFields->displayValue($type, $value)
            : (is_array($value) ? implode(', ', array_map('strval', $value)) : $value);
    }

    private function isSensitive(string $name, string $label, ?string $type = null): bool
    {
        return ($type !== null && ($this->smartFields->definition($type)['sensitive'] ?? false))
            || (bool) preg_match('/nik|npwp|rekening|account|password|token|secret|kata\s+sandi/i', $name.' '.$label);
    }

    private function presentValue(string $type, mixed $value): mixed
    {
        if (is_array($value) && isset($value['private_path'])) {
            return 'File terlampir';
        }
        if ($this->isFileList($value)) {
            return 'File terlampir ('.count($value).' file)';
        }

        return $this->smartFields->displayValue($type, $value);
    }

    private function isFileList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value) && $value !== []
            && collect($value)->every(fn (mixed $item): bool => is_array($item) && isset($item['private_path']));
    }
}
