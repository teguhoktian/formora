<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as LaravelValidator;

class FormSchemaService
{
    /** @var list<string> */
    public const FIELD_TYPES = ['text', 'textarea', 'email', 'phone', 'number', 'date', 'select', 'radio', 'checkbox', 'file', 'rating', 'slider', ...SmartFieldRegistry::TYPES];

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
            'rating' => 'Rating bintang', 'slider' => 'Slider angka',
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
            'settings' => match (true) {
                in_array($type, ['select', 'radio', 'checkbox'], true) => ['options' => [
                    ['label' => 'Pilihan 1', 'value' => 'pilihan_1'],
                    ['label' => 'Pilihan 2', 'value' => 'pilihan_2'],
                ]],
                $type === 'date' => ['mode' => 'date'],
                $type === 'rating' => ['scale' => 5],
                $type === 'slider' => ['min' => 0, 'max' => 100, 'step' => 1],
                default => [],
            },
            'validation' => [],
            'visibility' => null,
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
            'pages.*.fields.*' => ['required', 'array:id,type,name,label,required,help_text,default_value,settings,validation,visibility'],
            'pages.*.fields.*.id' => ['required', 'uuid', 'distinct'],
            'pages.*.fields.*.type' => ['required', Rule::in(self::FIELD_TYPES)],
            'pages.*.fields.*.name' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,63}$/'],
            'pages.*.fields.*.label' => ['required', 'string', 'max:120'],
            'pages.*.fields.*.required' => ['required', 'boolean'],
            'pages.*.fields.*.help_text' => ['nullable', 'string', 'max:500'],
            'pages.*.fields.*.default_value' => ['nullable', 'string', 'max:500'],
            'pages.*.fields.*.settings' => ['present', 'array'],
            'pages.*.fields.*.validation' => ['present', 'array'],
            'pages.*.fields.*.visibility' => ['nullable', 'array:field_id,operator,value'],
            'pages.*.fields.*.visibility.field_id' => ['required_with:pages.*.fields.*.visibility', 'uuid'],
            'pages.*.fields.*.visibility.operator' => ['required_with:pages.*.fields.*.visibility', Rule::in(['equals'])],
            'pages.*.fields.*.visibility.value' => ['required_with:pages.*.fields.*.visibility', 'string', 'max:64'],
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
                    } elseif ($type === 'date') {
                        if (! is_array($settings) || array_diff(array_keys($settings), ['mode']) !== [] || ! in_array($settings['mode'] ?? 'date', ['date', 'time', 'datetime', 'date_range'], true)) {
                            $validator->errors()->add("{$fieldPath}.settings", 'Pilih jenis tanggal, waktu, atau rentang yang tersedia.');
                        }
                    } elseif ($type === 'rating') {
                        $scale = is_array($settings) ? ($settings['scale'] ?? null) : null;
                        if (! is_array($settings) || array_keys($settings) !== ['scale'] || ! is_numeric($scale) || (float) (int) $scale !== (float) $scale || (int) $scale < 3 || (int) $scale > 10) {
                            $validator->errors()->add("{$fieldPath}.settings", 'Skala rating harus antara 3 dan 10 bintang.');
                        }
                    } elseif ($type === 'slider') {
                        $minimum = is_array($settings) ? ($settings['min'] ?? null) : null;
                        $maximum = is_array($settings) ? ($settings['max'] ?? null) : null;
                        $step = is_array($settings) ? ($settings['step'] ?? null) : null;
                        $intervals = is_numeric($minimum) && is_numeric($maximum) && is_numeric($step) && (float) $step > 0
                            ? ((float) $maximum - (float) $minimum) / (float) $step
                            : 0;
                        if (! is_array($settings) || array_diff(array_keys($settings), ['min', 'max', 'step']) !== [] || ! is_numeric($minimum) || ! is_numeric($maximum) || ! is_numeric($step) || (float) $minimum >= (float) $maximum || (float) $step <= 0 || abs((float) $minimum) > 1000000 || abs((float) $maximum) > 1000000 || $intervals > 100000 || abs($intervals - round($intervals)) > 0.000001) {
                            $validator->errors()->add("{$fieldPath}.settings", 'Rentang slider tidak valid. Atur batas minimum, maksimum, dan langkah angka.');
                        }
                    } elseif ($settings !== []) {
                        $validator->errors()->add("{$fieldPath}.settings", 'Pengaturan ini tidak berlaku untuk jenis field tersebut.');
                    }

                    $this->validateFieldValidation($validator, $field['validation'] ?? [], is_string($type) ? $type : '', "{$fieldPath}.validation");
                }

                $fieldsById = [];
                foreach ($fields as $field) {
                    if (is_array($field) && is_string($field['id'] ?? null)) {
                        $fieldsById[$field['id']] = $field;
                    }
                }

                foreach ($fields as $fieldIndex => $field) {
                    if (! is_array($field) || ! is_array($field['visibility'] ?? null)) {
                        continue;
                    }

                    $condition = $field['visibility'];
                    $path = "pages.{$pageIndex}.fields.{$fieldIndex}.visibility";
                    $sourceId = $condition['field_id'] ?? null;
                    $source = is_string($sourceId) ? ($fieldsById[$sourceId] ?? null) : null;
                    if ($source === null || $source['id'] === ($field['id'] ?? null)) {
                        $validator->errors()->add($path, 'Pilih field pemicu lain pada halaman yang sama.');

                        continue;
                    }
                    if (! in_array($source['type'] ?? null, ['select', 'radio'], true)) {
                        $validator->errors()->add($path, 'Field pemicu harus berupa pilihan dropdown atau pilihan tunggal.');

                        continue;
                    }
                    if (is_array($source['visibility'] ?? null)) {
                        $validator->errors()->add($path, 'Field pemicu harus selalu terlihat.');
                    }

                    $sourceOptions = $source['settings']['options'] ?? [];
                    $allowedValues = is_array($sourceOptions) ? array_column($sourceOptions, 'value') : [];
                    if (! is_string($condition['value'] ?? null) || ! in_array($condition['value'], $allowedValues, true)) {
                        $validator->errors()->add($path.'.value', 'Pilih jawaban yang tersedia pada field pemicu.');
                    }
                }
            }
        });

        return $validator->validate();
    }

    private function validateFieldValidation(LaravelValidator $validator, mixed $validation, string $type, string $path): void
    {
        if (! is_array($validation)) {
            return;
        }

        $common = ['custom_message'];
        $specific = match ($type) {
            'text', 'textarea' => ['min_length', 'max_length', 'contains', 'not_contains', 'pattern', 'pattern_mode'],
            'number' => ['operator', 'value', 'min', 'max'],
            'date' => ['min_date', 'max_date'],
            'checkbox' => ['min_selections', 'max_selections', 'exact_selections'],
            'file' => ['allowed_types', 'max_files', 'max_size_kb'],
            default => [],
        };
        $unknown = array_diff(array_keys($validation), [...$common, ...$specific]);
        if ($unknown !== []) {
            $validator->errors()->add($path, 'Aturan validasi tidak sesuai dengan jenis field.');

            return;
        }

        $message = $validation['custom_message'] ?? null;
        if ($message !== null && (! is_string($message) || mb_strlen($message) > 240)) {
            $validator->errors()->add($path.'.custom_message', 'Pesan kesalahan maksimal 240 karakter.');
        }

        $integerLimits = match ($type) {
            'text' => ['min_length' => 5000, 'max_length' => 5000],
            'textarea' => ['min_length' => 20000, 'max_length' => 20000],
            'checkbox' => ['min_selections' => 100, 'max_selections' => 100, 'exact_selections' => 100],
            'file' => ['max_files' => 5, 'max_size_kb' => 10240],
            default => [],
        };
        foreach ($integerLimits as $key => $limit) {
            $value = $validation[$key] ?? null;
            if ($value !== null && (! is_numeric($value) || (float) $value !== (float) (int) $value || (int) $value < 0 || (int) $value > $limit)) {
                $validator->errors()->add($path.'.'.$key, 'Nilai batas validasi tidak valid.');
            }
        }

        if (isset($validation['min_length'], $validation['max_length']) && (int) $validation['min_length'] > (int) $validation['max_length']) {
            $validator->errors()->add($path.'.max_length', 'Panjang maksimum harus sama dengan atau lebih besar dari minimum.');
        }

        if (in_array($type, ['text', 'textarea'], true)) {
            foreach (['contains', 'not_contains'] as $key) {
                if (isset($validation[$key]) && (! is_string($validation[$key]) || mb_strlen($validation[$key]) > 200)) {
                    $validator->errors()->add($path.'.'.$key, 'Nilai teks maksimal 200 karakter.');
                }
            }
            if (isset($validation['pattern'])) {
                $pattern = $validation['pattern'];
                $safePattern = is_string($pattern)
                    && trim($pattern) !== ''
                    && strlen($pattern) <= 128
                    && preg_match('/^[A-Za-z0-9\\s\\[\\]{}()*+?.|^$\\\\-]+$/', $pattern) === 1
                    && ! str_contains($pattern, '~')
                    && ! str_contains($pattern, '(')
                    && ! str_contains($pattern, ')')
                    && preg_match_all('/(?:[*+?]|\{\d+(?:,\d*)?\})/', $pattern) <= 8
                    && ! preg_match('/\.\*|\.\+/', $pattern)
                    && ! preg_match('/(?:[*+?]|\{\d+(?:,\d*)?\})(?:[*+?]|\{)/', $pattern)
                    && @preg_match('~'.$pattern.'~u', '') !== false;
                if (! $safePattern) {
                    $validator->errors()->add($path.'.pattern', 'Pola tidak valid atau terlalu kompleks.');
                }
            }
            if (isset($validation['pattern_mode']) && ! in_array($validation['pattern_mode'], ['matches', 'contains', 'not_contains', 'not_matches'], true)) {
                $validator->errors()->add($path.'.pattern_mode', 'Pilih jenis pencocokan pola yang tersedia.');
            }
            if (isset($validation['pattern']) xor isset($validation['pattern_mode'])) {
                $validator->errors()->add($path.'.pattern', 'Pola dan jenis pencocokan harus diisi bersama.');
            }
        }

        if ($type === 'number') {
            $operator = $validation['operator'] ?? null;
            if ($operator !== null && ! in_array($operator, ['between', 'equal', 'greater_than', 'at_least', 'less_than', 'at_most'], true)) {
                $validator->errors()->add($path.'.operator', 'Pilih aturan angka yang tersedia.');
            }
            if ($operator !== null && $operator !== 'between' && ! is_numeric($validation['value'] ?? null)) {
                $validator->errors()->add($path.'.value', 'Masukkan nilai angka untuk aturan ini.');
            }
            if ($operator === 'between' && (! is_numeric($validation['min'] ?? null) || ! is_numeric($validation['max'] ?? null) || (float) $validation['min'] > (float) $validation['max'])) {
                $validator->errors()->add($path.'.min', 'Rentang angka harus memiliki batas minimum dan maksimum yang valid.');
            }
            if ($operator !== null && $operator !== 'between' && (isset($validation['min']) || isset($validation['max']))) {
                $validator->errors()->add($path, 'Gunakan batas minimum dan maksimum hanya untuk aturan rentang.');
            }
            if ($operator === null && (isset($validation['value']) || isset($validation['min']) || isset($validation['max']))) {
                $validator->errors()->add($path, 'Pilih aturan angka sebelum memasukkan nilainya.');
            }
            if ($operator === 'between' && isset($validation['value'])) {
                $validator->errors()->add($path, 'Aturan rentang menggunakan batas minimum dan maksimum.');
            }
        }

        if ($type === 'date') {
            foreach (['min_date', 'max_date'] as $key) {
                $date = $validation[$key] ?? null;
                if ($date !== null) {
                    $dateParts = is_string($date) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches) === 1
                        ? $matches
                        : [];
                    if (count($dateParts) !== 4) {
                        $validator->errors()->add($path.'.'.$key, 'Tanggal batas tidak valid.');
                    } elseif (! checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1])) {
                        $validator->errors()->add($path.'.'.$key, 'Tanggal batas tidak valid.');
                    }
                }
            }
            if (isset($validation['min_date'], $validation['max_date']) && $validation['min_date'] > $validation['max_date']) {
                $validator->errors()->add($path.'.max_date', 'Tanggal akhir harus sama dengan atau setelah tanggal awal.');
            }
        }

        if ($type === 'checkbox' && isset($validation['exact_selections']) && (isset($validation['min_selections']) || isset($validation['max_selections']))) {
            $validator->errors()->add($path, 'Gunakan jumlah pilihan tepat atau batas minimum/maksimum, bukan keduanya.');
        }
        if ($type === 'checkbox'
            && isset($validation['min_selections'], $validation['max_selections'])
            && (int) $validation['min_selections'] > (int) $validation['max_selections']) {
            $validator->errors()->add($path.'.max_selections', 'Jumlah maksimum harus sama dengan atau lebih besar dari minimum.');
        }

        if ($type === 'file') {
            $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            $types = $validation['allowed_types'] ?? null;
            $typesValid = is_array($types) && $types !== [] && array_is_list($types);
            if ($typesValid) {
                foreach ($types as $fileType) {
                    if (! is_string($fileType) || ! in_array($fileType, $allowedTypes, true)) {
                        $typesValid = false;
                        break;
                    }
                }
            }
            if ($types !== null && ! $typesValid) {
                $validator->errors()->add($path.'.allowed_types', 'Pilih setidaknya satu jenis file yang didukung.');
            }
            if (isset($validation['max_files']) && (int) $validation['max_files'] < 1) {
                $validator->errors()->add($path.'.max_files', 'Jumlah file minimum adalah satu.');
            }
            if (isset($validation['max_size_kb']) && (int) $validation['max_size_kb'] < 1) {
                $validator->errors()->add($path.'.max_size_kb', 'Ukuran file minimum adalah 1 KB.');
            }
        }
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
