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

        $names = array_column($fields, 'name');
        $rules = [
            'website' => ['nullable', 'string', 'max:0'],
            'answers' => $names === []
                ? ['sometimes', 'array']
                : ['required', 'array:'.implode(',', $names)],
        ];
        $attributes = [];

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
                    $rules[$key] = [$presentRule, 'date_format:Y-m-d'];
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
                    $rules[$key.'.*'] = ['string', Rule::in($allowed)];
                    break;
                case 'file':
                    $rules[$key] = [$presentRule, 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'];
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
        }

        $validator = Validator::make($payload, $rules, [
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
        $validator->after(function ($validator) use ($payload): void {
            $totalBytes = 0;
            $answers = $payload['answers'] ?? [];
            if (is_array($answers)) {
                foreach ($answers as $answer) {
                    if ($answer instanceof UploadedFile) {
                        $totalBytes += $answer->getSize();
                    }
                }
            }

            if ($totalBytes > 25 * 1024 * 1024) {
                $validator->errors()->add('answers', 'Total ukuran file maksimal 25 MB.');
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
                if ($answer instanceof UploadedFile) {
                    $path = Storage::disk('local')->putFile('form-submissions/'.$form->id.'/'.$reference, $answer);
                    if ($path === false) {
                        throw new \RuntimeException('Private upload storage failed.');
                    }
                    $storedPaths[] = $path;
                    $answers[$name] = ['private_path' => $path];
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
