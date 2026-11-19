<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class PublicSubmissionService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validate(FormVersion $version, array $payload, FormSchemaService $schemas): array
    {
        $schema = $schemas->validate($version->schema);
        $fields = [];
        foreach ($schema['pages'] as $page) {
            foreach ($page['fields'] as $field) {
                $fields[] = $field;
            }
        }

        $incomingAnswers = $payload['answers'] ?? [];
        if (is_array($incomingAnswers)) {
            $payload['answers'] = app(SmartFieldRegistry::class)->normalizeAnswers($incomingAnswers, $fields);
        }

        $fieldsById = [];
        foreach ($fields as $field) {
            $fieldsById[$field['id']] = $field;
        }
        $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
        $visibleFields = [];
        $hiddenNames = [];
        foreach ($fields as $field) {
            $condition = $field['visibility'] ?? null;
            if (is_array($condition)) {
                $source = $fieldsById[$condition['field_id']] ?? null;
                $sourceAnswer = is_array($source) ? ($answers[$source['name']] ?? null) : null;
                if (! is_string($sourceAnswer) || $sourceAnswer !== $condition['value']) {
                    $hiddenNames[$field['name']] = true;

                    continue;
                }
            }
            $visibleFields[] = $field;
        }
        $fields = $visibleFields;
        if (is_array($payload['answers'] ?? null)) {
            $payload['answers'] = array_diff_key($payload['answers'], $hiddenNames);
        }

        $names = array_column($fields, 'name');
        $rules = [
            'website' => ['nullable', 'string', 'max:0'],
            'answers' => $names === []
                ? ['sometimes', 'array']
                : ['required', 'array:'.implode(',', $names)],
        ];
        $attributes = [];
        $messages = [];

        foreach ($fields as $field) {
            $key = 'answers.'.$field['name'];
            $attributes[$key] = $field['label'];
            $presentRule = $field['required'] ? 'required' : 'nullable';

            switch ($field['type']) {
                case 'text':
                    $rules[$key] = [$presentRule, 'string', 'max:5000'];
                    break;
                case 'textarea':
                    $rules[$key] = [$presentRule, 'string', 'max:20000'];
                    break;
                case 'email':
                    $rules[$key] = [$presentRule, 'email', 'max:254'];
                    break;
                case 'phone':
                    $rules[$key] = [$presentRule, 'string', 'max:50'];
                    break;
                case 'number':
                    $rules[$key] = [$presentRule, 'numeric'];
                    break;
                case 'date':
                    $mode = $field['settings']['mode'] ?? 'date';
                    if ($mode === 'date_range') {
                        $rules[$key] = $field['required'] ? ['required', 'array:start,end'] : ['sometimes', 'array:start,end'];
                        foreach (['start', 'end'] as $bound) {
                            $dateKey = $key.'.'.$bound;
                            $rules[$dateKey] = [$field['required'] ? 'required' : 'nullable', 'date_format:Y-m-d'];
                            if (isset($field['validation']['min_date'])) {
                                $rules[$dateKey][] = 'after_or_equal:'.$field['validation']['min_date'];
                            }
                            if (isset($field['validation']['max_date'])) {
                                $rules[$dateKey][] = 'before_or_equal:'.$field['validation']['max_date'];
                            }
                        }
                    } else {
                        $dateFormat = match ($mode) {
                            'time' => 'H:i',
                            'datetime' => 'Y-m-d\\TH:i',
                            default => 'Y-m-d',
                        };
                        $rules[$key] = [$presentRule, 'date_format:'.$dateFormat];
                        if ($mode === 'date') {
                            if (isset($field['validation']['min_date'])) {
                                $rules[$key][] = 'after_or_equal:'.$field['validation']['min_date'];
                            }
                            if (isset($field['validation']['max_date'])) {
                                $rules[$key][] = 'before_or_equal:'.$field['validation']['max_date'];
                            }
                        }
                    }
                    break;
                case 'rating':
                    $rules[$key] = [$presentRule, 'integer', Rule::in(range(1, (int) $field['settings']['scale']))];
                    break;
                case 'slider':
                    $rules[$key] = [$presentRule, 'numeric', 'min:'.$field['settings']['min'], 'max:'.$field['settings']['max']];
                    break;
                case 'select':
                case 'radio':
                    $allowed = array_column($field['settings']['options'], 'value');
                    $rules[$key] = [$presentRule, 'string', Rule::in($allowed)];
                    break;
                case 'checkbox':
                    $allowed = array_column($field['settings']['options'], 'value');
                    $rules[$key] = $field['required']
                        ? ['required', 'array', 'min:1', 'max:100']
                        : ['sometimes', 'array', 'max:100'];
                    $validation = $field['validation'] ?? [];
                    if (isset($validation['exact_selections'])) {
                        $rules[$key][] = 'size:'.$validation['exact_selections'];
                    } else {
                        if (isset($validation['min_selections'])) {
                            $rules[$key][] = 'min:'.$validation['min_selections'];
                        }
                        if (isset($validation['max_selections'])) {
                            $rules[$key][] = 'max:'.$validation['max_selections'];
                        }
                    }
                    $rules[$key.'.*'] = ['string', Rule::in($allowed)];
                    break;
                case 'file':
                    $validation = $field['validation'] ?? [];
                    $maxFiles = (int) ($validation['max_files'] ?? 1);
                    $extensions = $validation['allowed_types'] ?? ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                    $maxSize = (int) ($validation['max_size_kb'] ?? 10240);
                    if ($maxFiles > 1) {
                        $rules[$key] = $field['required'] ? ['required', 'array', 'min:1', 'max:'.$maxFiles] : ['sometimes', 'array', 'max:'.$maxFiles];
                        $rules[$key.'.*'] = ['file', 'mimes:'.implode(',', $extensions), 'max:'.$maxSize];
                    } else {
                        $rules[$key] = [$presentRule, 'file', 'mimes:'.implode(',', $extensions), 'max:'.$maxSize];
                    }
                    break;
                case 'smart_address':
                    $regions = app(IndonesiaRegionCatalog::class);
                    $parts = ['province' => 'Provinsi', 'regency' => 'Kabupaten/Kota', 'district' => 'Kecamatan', 'village' => 'Desa/Kelurahan'];
                    $rules[$key] = [$presentRule, 'array:'.implode(',', array_keys($parts))];
                    $allAnswers = $payload['answers'] ?? null;
                    $address = is_array($allAnswers) && is_array($allAnswers[$field['name']] ?? null)
                        ? $allAnswers[$field['name']]
                        : [];
                    $selectedProvince = is_string($address['province'] ?? null) ? $address['province'] : '';
                    $selectedRegency = is_string($address['regency'] ?? null) ? $address['regency'] : '';
                    $selectedDistrict = is_string($address['district'] ?? null) ? $address['district'] : '';
                    $codes = [
                        'province' => $regions->provinceCodes(),
                        'regency' => $regions->regencyCodes($selectedProvince),
                        'district' => $regions->districtCodes($selectedRegency),
                        'village' => $regions->villageCodes($selectedDistrict),
                    ];
                    foreach ($parts as $part => $partLabel) {
                        $partKey = $key.'.'.$part;
                        $rules[$partKey] = [$field['required'] ? 'required' : 'nullable', 'string', Rule::in($codes[$part])];
                        $attributes[$partKey] = $partLabel;
                    }
                    break;
                default:
                    $definition = app(SmartFieldRegistry::class)->definition($field['type']);
                    if ($definition !== null) {
                        $rules[$key] = $field['type'] === 'smart_bank_name'
                            ? [$presentRule, ...$definition['rules'], Rule::in($definition['options'] ?? [])]
                            : [$presentRule, ...$definition['rules']];
                    }
                    break;
            }

            $fieldMessage = $field['validation']['custom_message'] ?? null;
            if (is_string($fieldMessage) && $fieldMessage !== '') {
                $messages[$key] = $fieldMessage;
                $messages[$key.'.*'] = $fieldMessage;
            }
        }

        $validator = Validator::make($payload, $rules, $messages + [
            'required' => 'Isian :attribute wajib diisi.',
            'digits' => 'Isian :attribute harus terdiri dari :digits digit angka.',
            'regex' => 'Format :attribute tidak valid.',
            'email' => 'Format :attribute tidak valid.',
            'date_format' => 'Format tanggal :attribute tidak valid.',
            'before_or_equal' => ':attribute tidak boleh melebihi hari ini.',
            'numeric' => ':attribute harus berupa angka.',
            'min.numeric' => ':attribute tidak boleh kurang dari :min.',
            'in' => 'Pilihan :attribute tidak valid.',
            'array' => 'Isian :attribute tidak valid.',
            'string' => 'Isian :attribute harus berupa teks.',
            'mimes' => 'Format file :attribute tidak didukung.',
            'max.file' => 'Ukuran file :attribute melebihi batas.',
            'max.string' => 'Isian :attribute melewati batas karakter.',
        ])->setAttributeNames($attributes);
        $validator->after(function ($validator) use ($payload, $fields): void {
            $totalBytes = 0;
            $answers = $payload['answers'] ?? [];
            if (is_array($answers)) {
                foreach ($answers as $answer) {
                    foreach (is_array($answer) ? $answer : [$answer] as $file) {
                        if ($file instanceof UploadedFile) {
                            $totalBytes += $file->getSize();
                        }
                    }
                }
            }

            if ($totalBytes > 25 * 1024 * 1024) {
                $validator->errors()->add('answers', 'Total ukuran file maksimal 25 MB.');
            }

            foreach ($fields as $field) {
                $name = $field['name'];
                $validation = $field['validation'] ?? [];
                $value = data_get($payload, 'answers.'.$name);
                if ($value === null || $value === '') {
                    continue;
                }
                $errorKey = 'answers.'.$name;
                $custom = is_string($validation['custom_message'] ?? null) && $validation['custom_message'] !== ''
                    ? $validation['custom_message']
                    : null;
                $fail = fn (string $message) => $validator->errors()->add($errorKey, $custom ?? $message);

                if (in_array($field['type'], ['text', 'textarea'], true) && is_string($value)) {
                    $length = mb_strlen($value);
                    if (isset($validation['min_length']) && $length < (int) $validation['min_length']) {
                        $fail('Jawaban '.$field['label'].' terlalu pendek.');
                    }
                    if (isset($validation['max_length']) && $length > (int) $validation['max_length']) {
                        $fail('Jawaban '.$field['label'].' terlalu panjang.');
                    }
                    if (isset($validation['contains']) && ! str_contains(mb_strtolower($value), mb_strtolower($validation['contains']))) {
                        $fail('Jawaban '.$field['label'].' harus memuat teks yang diminta.');
                    }
                    if (isset($validation['not_contains']) && str_contains(mb_strtolower($value), mb_strtolower($validation['not_contains']))) {
                        $fail('Jawaban '.$field['label'].' memuat teks yang tidak diizinkan.');
                    }
                    if (isset($validation['pattern'])) {
                        $matches = preg_match('~'.$validation['pattern'].'~u', $value) === 1;
                        $mode = $validation['pattern_mode'];
                        $valid = match ($mode) {
                            'matches' => preg_match('~^(?:'.$validation['pattern'].')$~u', $value) === 1,
                            'contains' => $matches,
                            'not_contains' => ! $matches,
                            'not_matches' => preg_match('~^(?:'.$validation['pattern'].')$~u', $value) !== 1,
                            default => true,
                        };
                        if (! $valid) {
                            $fail('Format jawaban '.$field['label'].' tidak sesuai.');
                        }
                    }
                }

                if ($field['type'] === 'number' && is_numeric($value)) {
                    $number = (float) $value;
                    $operator = $validation['operator'] ?? null;
                    $limit = isset($validation['value']) ? (float) $validation['value'] : null;
                    $valid = match ($operator) {
                        'between' => $number >= (float) $validation['min'] && $number <= (float) $validation['max'],
                        'equal' => $limit !== null && $number === $limit,
                        'greater_than' => $limit !== null && $number > $limit,
                        'at_least' => $limit !== null && $number >= $limit,
                        'less_than' => $limit !== null && $number < $limit,
                        'at_most' => $limit !== null && $number <= $limit,
                        default => true,
                    };
                    if (! $valid) {
                        $fail('Nilai '.$field['label'].' tidak memenuhi batas yang ditentukan.');
                    }
                }

                if ($field['type'] === 'slider' && is_numeric($value)) {
                    $settings = $field['settings'];
                    $steps = ((float) $value - (float) $settings['min']) / (float) $settings['step'];
                    if (abs($steps - round($steps)) > 0.000001) {
                        $fail('Pilih nilai slider pada langkah yang tersedia.');
                    }
                }

                if ($field['type'] === 'date' && ($field['settings']['mode'] ?? 'date') === 'date_range' && is_array($value)
                    && is_string($value['start'] ?? null) && is_string($value['end'] ?? null) && $value['start'] > $value['end']) {
                    $fail('Tanggal akhir '.$field['label'].' harus setelah tanggal awal.');
                }
            }
        });

        return $validator->validate();
    }

    /** @param array<string, mixed> $validated */
    public function store(Form $form, FormVersion $version, array $validated): FormSubmission
    {
        $reference = 'FM-'.Str::upper((string) Str::ulid());
        $answers = $validated['answers'] ?? [];
        $storedPaths = [];

        try {
            foreach ($answers as $name => $answer) {
                $files = is_array($answer) ? $answer : [$answer];
                $storedFiles = [];
                foreach ($files as $file) {
                    if (! $file instanceof UploadedFile) {
                        continue;
                    }
                    $path = Storage::disk('local')->putFile('form-submissions/'.$form->id.'/'.$reference, $file);
                    if ($path === false) {
                        throw new \RuntimeException('Private upload storage failed.');
                    }
                    $storedPaths[] = $path;
                    $storedFiles[] = ['private_path' => $path, 'original_name' => basename($file->getClientOriginalName())];
                }
                if ($storedFiles !== []) {
                    $answers[$name] = is_array($answer) ? $storedFiles : $storedFiles[0];
                }
            }

            return DB::transaction(fn (): FormSubmission => FormSubmission::query()->create([
                'workspace_id' => $form->workspace_id,
                'form_id' => $form->id,
                'form_version_id' => $version->id,
                'status' => 'new',
                'response_data' => $answers,
                'reference_number' => $reference,
            ]));
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }
}
