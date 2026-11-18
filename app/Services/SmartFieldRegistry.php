<?php

namespace App\Services;

/** @phpstan-type SmartFieldDefinition array{label: string, toolbox_label: string, input_type: string, inputmode: string, placeholder: string, help_text: string, sensitive: bool, numeric_only: bool, max_length: int, rules: list<string>} */
class SmartFieldRegistry
{
    public const TYPES = [
        'smart_nik', 'smart_npwp', 'smart_birth_date', 'smart_bank_account',
        'smart_bank_name', 'smart_rupiah', 'smart_id_phone', 'smart_address',
        'smart_nip', 'smart_nisn', 'smart_nuptk', 'smart_nim',
    ];

    /** @return array<string, SmartFieldDefinition> */
    public function definitions(): array
    {
        return [
            'smart_nik' => ['label' => 'NIK', 'toolbox_label' => 'NIK', 'input_type' => 'text', 'inputmode' => 'numeric', 'placeholder' => '16 digit angka', 'help_text' => 'Format 16 digit angka. Isian tidak diverifikasi ke Dukcapil.', 'sensitive' => true, 'numeric_only' => true, 'max_length' => 16, 'rules' => ['digits:16']],
            'smart_npwp' => ['label' => 'NPWP', 'toolbox_label' => 'NPWP', 'input_type' => 'text', 'inputmode' => 'numeric', 'placeholder' => '15 atau 16 digit angka', 'help_text' => 'Format NPWP 15 atau 16 digit. Isian tidak diverifikasi ke DJP.', 'sensitive' => true, 'numeric_only' => true, 'max_length' => 16, 'rules' => ['regex:/^(?:[0-9]{15}|[0-9]{16})$/']],
            'smart_birth_date' => ['label' => 'Tanggal lahir', 'toolbox_label' => 'Tgl. lahir', 'input_type' => 'date', 'inputmode' => 'none', 'placeholder' => '', 'help_text' => 'Tanggal lahir tidak boleh di masa depan.', 'sensitive' => true, 'numeric_only' => false, 'max_length' => 10, 'rules' => ['date_format:Y-m-d', 'before_or_equal:today']],
            'smart_bank_account' => ['label' => 'Nomor rekening bank', 'toolbox_label' => 'Rekening', 'input_type' => 'text', 'inputmode' => 'numeric', 'placeholder' => 'Nomor rekening', 'help_text' => 'Masukkan angka rekening. Format tidak diverifikasi ke bank.', 'sensitive' => true, 'numeric_only' => true, 'max_length' => 30, 'rules' => ['regex:/^[0-9]{6,30}$/']],
            'smart_bank_name' => ['label' => 'Nama bank', 'toolbox_label' => 'Nama bank', 'input_type' => 'text', 'inputmode' => 'text', 'placeholder' => 'Nama bank', 'help_text' => 'Nama bank diisi sebagai teks.', 'sensitive' => false, 'numeric_only' => false, 'max_length' => 120, 'rules' => ['string', 'max:120']],
            'smart_rupiah' => ['label' => 'Nominal Rupiah', 'toolbox_label' => 'Rupiah', 'input_type' => 'number', 'inputmode' => 'decimal', 'placeholder' => '0', 'help_text' => 'Masukkan nominal angka tanpa simbol mata uang.', 'sensitive' => false, 'numeric_only' => false, 'max_length' => 30, 'rules' => ['numeric', 'min:0']],
            'smart_id_phone' => ['label' => 'Nomor telepon Indonesia', 'toolbox_label' => 'Telepon ID', 'input_type' => 'tel', 'inputmode' => 'tel', 'placeholder' => '08xx atau +628xx', 'help_text' => 'Format nomor seluler Indonesia; tidak diverifikasi ke operator.', 'sensitive' => true, 'numeric_only' => false, 'max_length' => 20, 'rules' => ['regex:/^(?:\\+62|62|0)8[0-9]{7,12}$/']],
            'smart_address' => ['label' => 'Alamat bertingkat', 'toolbox_label' => 'Alamat', 'input_type' => 'address', 'inputmode' => 'text', 'placeholder' => '', 'help_text' => 'Isi provinsi, kabupaten/kota, kecamatan, dan desa/kelurahan.', 'sensitive' => false, 'numeric_only' => false, 'max_length' => 120, 'rules' => []],
            'smart_nip' => ['label' => 'NIP', 'toolbox_label' => 'NIP', 'input_type' => 'text', 'inputmode' => 'numeric', 'placeholder' => '18 digit angka', 'help_text' => 'Format 18 digit angka. Isian tidak diverifikasi ke BKN.', 'sensitive' => true, 'numeric_only' => true, 'max_length' => 18, 'rules' => ['digits:18']],
            'smart_nisn' => ['label' => 'NISN', 'toolbox_label' => 'NISN', 'input_type' => 'text', 'inputmode' => 'numeric', 'placeholder' => '10 digit angka', 'help_text' => 'Format 10 digit angka. Isian tidak diverifikasi ke layanan pendidikan.', 'sensitive' => true, 'numeric_only' => true, 'max_length' => 10, 'rules' => ['digits:10']],
            'smart_nuptk' => ['label' => 'NUPTK', 'toolbox_label' => 'NUPTK', 'input_type' => 'text', 'inputmode' => 'numeric', 'placeholder' => '16 digit angka', 'help_text' => 'Format 16 digit angka. Isian tidak diverifikasi ke layanan pendidikan.', 'sensitive' => true, 'numeric_only' => true, 'max_length' => 16, 'rules' => ['digits:16']],
            'smart_nim' => ['label' => 'NIM', 'toolbox_label' => 'NIM', 'input_type' => 'text', 'inputmode' => 'text', 'placeholder' => 'Nomor induk mahasiswa', 'help_text' => 'Format NIM berbeda antarperguruan tinggi; isian tidak diverifikasi.', 'sensitive' => true, 'numeric_only' => false, 'max_length' => 50, 'rules' => ['string', 'max:50']],
        ];
    }

    /** @return SmartFieldDefinition|null */
    public function definition(string $type): ?array
    {
        return $this->definitions()[$type] ?? null;
    }

    /**
     * @param  array<string, mixed>  $answers
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    public function normalizeAnswers(array $answers, array $fields): array
    {
        foreach ($fields as $field) {
            $type = $field['type'] ?? null;
            $name = $field['name'] ?? null;
            if (! is_string($type) || ! is_string($name) || ! array_key_exists($name, $answers) || ! is_string($answers[$name])) {
                continue;
            }

            if (in_array($type, ['smart_npwp', 'smart_bank_account'], true)) {
                $answers[$name] = preg_replace('/[.\\s-]+/', '', $answers[$name]) ?? $answers[$name];
            } elseif ($type === 'smart_id_phone') {
                $answers[$name] = preg_replace('/[\\s().-]+/', '', $answers[$name]) ?? $answers[$name];
            }
        }

        return $answers;
    }

    public function displayValue(string $type, mixed $value): mixed
    {
        if ($type === 'smart_rupiah' && is_numeric($value)) {
            return 'Rp '.number_format((float) $value, 0, ',', '.');
        }

        if ($type === 'smart_address' && is_array($value)) {
            $ordered = [];
            $provinceCode = is_string($value['province'] ?? null) ? $value['province'] : '';
            $regencyCode = is_string($value['regency'] ?? null) ? $value['regency'] : '';
            $districtCode = is_string($value['district'] ?? null) ? $value['district'] : '';
            $villageCode = is_string($value['village'] ?? null) ? $value['village'] : '';
            $provinceName = app(IndonesiaRegionCatalog::class)->provinceName($provinceCode);
            $regencyName = app(IndonesiaRegionCatalog::class)->regencyName($provinceCode, $regencyCode);
            $districtName = app(IndonesiaRegionCatalog::class)->districtName($regencyCode, $districtCode);
            $villageName = app(IndonesiaRegionCatalog::class)->villageName($districtCode, $villageCode);
            if ($provinceName !== null) {
                $ordered[] = 'Provinsi: '.$provinceName;
            }
            if ($regencyName !== null) {
                $ordered[] = 'Kabupaten/Kota: '.$regencyName;
            }
            if ($districtName !== null) {
                $ordered[] = 'Kecamatan: '.$districtName;
            }
            if ($villageName !== null) {
                $ordered[] = 'Desa/Kelurahan: '.$villageName;
            }

            return implode(', ', $ordered);
        }

        return $value;
    }
}
