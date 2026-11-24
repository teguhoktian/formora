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
                : $this->presentValue($field['type'], $value, $field['date_mode'], $field['rating_scale'], $field['options']);
        }

        return $result;
    }

    /** @param array<string, mixed> $schema
     * @return array<string, array{name: string, label: string, type: string, date_mode: ?string, rating_scale: ?int, options: array<string, string>}>
     */
    public function fields(array $schema): array
    {
        $fields = [];
        foreach ($schema['pages'] ?? [] as $page) {
            foreach ($page['fields'] ?? [] as $field) {
                if (isset($field['name'], $field['label'], $field['type']) && is_string($field['name']) && is_string($field['label']) && is_string($field['type'])) {
                    $options = [];
                    foreach ($field['settings']['options'] ?? [] as $option) {
                        if (is_array($option) && is_string($option['value'] ?? null) && is_string($option['label'] ?? null)) {
                            $options[$option['value']] = $option['label'];
                        }
                    }
                    $fields[$field['name']] = ['name' => $field['name'], 'label' => $field['label'], 'type' => $field['type'], 'date_mode' => $field['settings']['mode'] ?? null, 'rating_scale' => isset($field['settings']['scale']) ? (int) $field['settings']['scale'] : null, 'options' => $options];
                }
            }
        }

        return $fields;
    }

    /** @return array<string, list<array{name: string, field: string, index: int}>> */
    public function fileAttachments(FormSubmission $submission): array
    {
        $fields = $this->fields($submission->formVersion->schema);
        $attachments = [];
        foreach ($submission->response_data as $name => $value) {
            if (! in_array($fields[$name]['type'] ?? null, ['file', 'signature'], true)) {
                continue;
            }
            $files = is_array($value) && array_is_list($value) ? $value : [$value];
            foreach ($files as $index => $file) {
                if (! is_array($file) || ! isset($file['private_path'])) {
                    continue;
                }
                $originalName = $file['original_name'] ?? null;
                $attachments[$fields[$name]['label']][] = [
                    'name' => is_string($originalName) && $originalName !== '' ? basename($originalName) : 'File terlampir',
                    'field' => $name,
                    'index' => $index,
                ];
            }
        }

        return $attachments;
    }

    /** @return array<string, mixed> */
    public function exportValues(FormSubmission $submission): array
    {
        $fields = $this->fields($submission->formVersion->schema);
        $values = [];
        foreach ($fields as $name => $field) {
            if (! array_key_exists($name, $submission->response_data)) {
                continue;
            }
            $value = $submission->response_data[$name];
            if ($this->isSensitive($name, $field['label'], $field['type'])) {
                $values[$name] = '••••••••';
            } elseif ($field['type'] === 'file') {
                $files = is_array($value) && array_is_list($value) ? $value : [$value];
                $names = [];
                foreach ($files as $file) {
                    if (is_array($file) && isset($file['private_path'])) {
                        $originalName = $file['original_name'] ?? null;
                        $names[] = is_string($originalName) && $originalName !== '' ? basename($originalName) : 'File terlampir';
                    }
                }
                $values[$name] = implode(', ', $names);
            } else {
                $values[$name] = $this->presentValue($field['type'], $value, $field['date_mode'], $field['rating_scale'], $field['options']);
            }
        }

        return $values;
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

    /** @param array<string, string> $options */
    private function presentValue(string $type, mixed $value, ?string $dateMode = null, ?int $ratingScale = null, array $options = []): mixed
    {
        if (is_array($value) && isset($value['private_path'])) {
            return 'File terlampir';
        }
        if ($this->isFileList($value)) {
            return 'File terlampir ('.count($value).' file)';
        }
        if ($dateMode === 'date_range' && is_array($value)) {
            $start = is_string($value['start'] ?? null) ? $value['start'] : '—';
            $end = is_string($value['end'] ?? null) ? $value['end'] : '—';

            return $start.' sampai '.$end;
        }
        if ($type === 'rating' && is_numeric($value)) {
            $scale = $ratingScale ?? 5;
            $rating = max(0, min($scale, (int) $value));

            return str_repeat('★', $rating).str_repeat('☆', $scale - $rating).' ('.$rating.'/'.$scale.')';
        }

        if (in_array($type, ['select', 'radio'], true) && is_string($value)) {
            return $options[$value] ?? str($value)->replace('_', ' ')->title()->toString();
        }
        if ($type === 'checkbox' && is_array($value)) {
            return implode(', ', array_map(fn (mixed $item): string => is_string($item)
                ? ($options[$item] ?? str($item)->replace('_', ' ')->title()->toString())
                : '', $value));
        }

        return $this->smartFields->displayValue($type, $value);
    }

    private function isFileList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value) && $value !== []
            && collect($value)->every(fn (mixed $item): bool => is_array($item) && isset($item['private_path']));
    }
}
