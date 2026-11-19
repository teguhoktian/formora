<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FormSchemaService
{
    /** @var list<string> */
    public const FIELD_TYPES = ['text', 'textarea', 'email', 'phone', 'number', 'date', 'select', 'radio', 'checkbox', 'file', ...SmartFieldRegistry::TYPES];

    /** @return array<string, mixed> */
    public function empty(): array
    {
        return [
            'schema_version' => 1,
            'pages' => [$this->newPage('Halaman 1')],
            'settings' => [
                'submit_label' => 'Kirim',
                'success_message' => 'Terima kasih, data berhasil dikirim.',
                'appearance' => ['accent' => 'emerald', 'width' => 'standard'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function newPage(string $title): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => $title,
            'description' => null,
            'fields' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function newField(string $type, array $schema): array
    {
        abort_unless(in_array($type, self::FIELD_TYPES, true), 422);

        $id = (string) Str::uuid();
        $labels = [
            'text' => 'Teks singkat', 'textarea' => 'Paragraf', 'email' => 'Email',
            'phone' => 'Telepon', 'number' => 'Angka', 'date' => 'Tanggal',
            'select' => 'Pilihan', 'radio' => 'Pilihan tunggal', 'checkbox' => 'Kotak centang', 'file' => 'Unggah file',
        ];
        $smartDefinition = app(SmartFieldRegistry::class)->definition($type);
        $nameBase = 'field_'.str_replace('-', '', Str::substr($id, 0, 8));

        return [
            'id' => $id,
            'type' => $type,
            'name' => $this->uniqueName($schema, $nameBase),
            'label' => $smartDefinition['label'] ?? ($labels[$type] ?? $type),
            'required' => false,
            'help_text' => null,
            'default_value' => null,
            'settings' => in_array($type, ['select', 'radio', 'checkbox'], true)
                ? ['options' => [
                    ['label' => 'Pilihan 1', 'value' => 'pilihan_1'],
                    ['label' => 'Pilihan 2', 'value' => 'pilihan_2'],
                ]]
                : [],
            'validation' => [],
        ];
    }

    /** @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public function validate(array $schema): array
    {
        $validator = Validator::make($schema, [
            'schema_version' => ['required', 'integer', Rule::in([1])],
            'pages' => ['required', 'array', 'min:1', 'max:20'],
            'pages.*' => ['required', 'array:id,title,description,fields'],
            'pages.*.id' => ['required', 'uuid', 'distinct'],
            'pages.*.title' => ['required', 'string', 'max:120'],
            'pages.*.description' => ['nullable', 'string', 'max:500'],
            'pages.*.fields' => ['present', 'array', 'max:100'],
            'pages.*.fields.*' => ['required', 'array:id,type,name,label,required,help_text,default_value,settings,validation'],
            'pages.*.fields.*.id' => ['required', 'uuid', 'distinct'],
            'pages.*.fields.*.type' => ['required', Rule::in(self::FIELD_TYPES)],
            'pages.*.fields.*.name' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,63}$/'],
            'pages.*.fields.*.label' => ['required', 'string', 'max:120'],
            'pages.*.fields.*.required' => ['required', 'boolean'],
            'pages.*.fields.*.help_text' => ['nullable', 'string', 'max:500'],
            'pages.*.fields.*.default_value' => ['nullable', 'string', 'max:500'],
            'pages.*.fields.*.settings' => ['present', 'array'],
            'pages.*.fields.*.validation' => ['present', 'array'],
            'settings' => ['required', 'array:submit_label,success_message,appearance'],
            'settings.submit_label' => ['required', 'string', 'max:80'],
            'settings.success_message' => ['required', 'string', 'max:500'],
            'settings.appearance' => ['sometimes', 'array:accent,width'],
            'settings.appearance.accent' => ['sometimes', 'required', Rule::in(['emerald', 'blue', 'violet'])],
            'settings.appearance.width' => ['sometimes', 'required', Rule::in(['compact', 'standard', 'wide'])],
        ]);

        $validator->after(function ($validator) use ($schema): void {
            $names = [];
            $fieldIds = [];
            $pages = $schema['pages'] ?? [];
            if (! is_array($pages)) {
                return;
            }

            foreach ($pages as $pageIndex => $page) {
                if (! is_array($page)) {
                    continue;
                }
                $fields = $page['fields'] ?? [];
                if (! is_array($fields)) {
                    continue;
                }

                foreach ($fields as $fieldIndex => $field) {
                    if (! is_array($field)) {
                        continue;
                    }

                    $fieldPath = "pages.{$pageIndex}.fields.{$fieldIndex}";
                    $name = $field['name'] ?? null;
                    if (is_string($name)) {
                        $normalizedName = strtolower($name);
                        if (isset($names[$normalizedName])) {
                            $validator->errors()->add("{$fieldPath}.name", 'Nama field harus unik dalam satu form.');
                        }
                        $names[$normalizedName] = true;
                    }

                    $id = $field['id'] ?? null;
                    if (is_string($id)) {
                        if (isset($fieldIds[$id])) {
                            $validator->errors()->add("{$fieldPath}.id", 'ID field harus unik.');
                        }
                        $fieldIds[$id] = true;
                    }

                    $type = $field['type'] ?? null;
                    $settings = $field['settings'] ?? [];
                    if (in_array($type, ['select', 'radio', 'checkbox'], true)) {
                        if (! is_array($settings) || array_keys($settings) !== ['options']) {
                            $validator->errors()->add("{$fieldPath}.settings", 'Pengaturan pilihan tidak valid.');
                        }
                        $options = is_array($settings) ? ($settings['options'] ?? null) : null;
                        if (! is_array($options) || count($options) < 1 || count($options) > 100) {
                            $validator->errors()->add("{$fieldPath}.settings.options", 'Tambahkan antara 1 dan 100 pilihan.');

                            continue;
                        }

                        $values = [];
                        foreach ($options as $optionIndex => $option) {
                            if (! is_array($option)
                                || array_diff(array_keys($option), ['label', 'value']) !== []
                                || ! isset($option['label'], $option['value'])
                                || ! is_string($option['label'])
                                || ! is_string($option['value'])
                                || trim($option['label']) === ''
                                || mb_strlen($option['label']) > 120
                                || ! preg_match('/^[a-z][a-z0-9_]{0,63}$/', $option['value'])) {
                                $validator->errors()->add("{$fieldPath}.settings.options.{$optionIndex}", 'Pilihan tidak valid.');

                                continue;
                            }

                            if (isset($values[$option['value']])) {
                                $validator->errors()->add("{$fieldPath}.settings.options.{$optionIndex}.value", 'Nilai pilihan harus unik.');
                            }
                            $values[$option['value']] = true;
                        }
                    } elseif ($settings !== []) {
                        $validator->errors()->add("{$fieldPath}.settings", 'Pengaturan ini tidak berlaku untuk jenis field tersebut.');
                    }

                    if (($field['validation'] ?? []) !== []) {
                        $validator->errors()->add("{$fieldPath}.validation", 'Aturan validasi tambahan belum tersedia.');
                    }
                }
            }
        });

        return $validator->validate();
    }

    /** @param array<string, mixed> $schema */
    public function uniqueName(array $schema, string $base): string
    {
        $existing = [];
        foreach (($schema['pages'] ?? []) as $page) {
            foreach (($page['fields'] ?? []) as $field) {
                if (isset($field['name']) && is_string($field['name'])) {
                    $existing[] = strtolower($field['name']);
                }
            }
        }

        $name = $base;
        $suffix = 2;
        while (in_array(strtolower($name), $existing, true)) {
            $name = $base.'_'.$suffix++;
        }

        return $name;
    }
}
