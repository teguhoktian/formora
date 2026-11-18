<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class IndonesiaRegionCatalog
{
    /** @var list<array{code: string, name: string}>|null */
    private ?array $provincesCache = null;

    /** @var list<array{code: string, name: string, parent_code: string}>|null */
    private ?array $regenciesCache = null;

    /** @return list<array{code: string, name: string}> */
    public function provinces(): array
    {
        if ($this->provincesCache !== null) {
            return $this->provincesCache;
        }

        $provinces = $this->readJsonList('indonesia-provinces.json');
        foreach ($provinces as $province) {
            abort_unless(is_string($province['code'] ?? null) && is_string($province['name'] ?? null), 503, 'Data provinsi tidak valid.');
        }

        return $this->provincesCache = $provinces;
    }

    /** @return list<array{code: string, name: string}> */
    public function regencies(string $provinceCode): array
    {
        return array_map(
            fn (array $region): array => ['code' => $region['code'], 'name' => $region['name']],
            array_values(array_filter($this->allRegencies(), fn (array $region): bool => $region['parent_code'] === $provinceCode)),
        );
    }

    /** @return list<array{code: string, name: string}> */
    public function districts(string $regencyCode): array
    {
        return $this->children('indonesia-districts.jsonl', $regencyCode);
    }

    /** @return list<array{code: string, name: string}> */
    public function villages(string $districtCode): array
    {
        return $this->children('indonesia-villages.jsonl', $districtCode);
    }

    /** @return list<string> */
    public function provinceCodes(): array
    {
        return array_column($this->provinces(), 'code');
    }

    /** @return list<string> */
    public function regencyCodes(string $provinceCode): array
    {
        return array_column($this->regencies($provinceCode), 'code');
    }

    /** @return list<string> */
    public function districtCodes(string $regencyCode): array
    {
        return array_column($this->districts($regencyCode), 'code');
    }

    /** @return list<string> */
    public function villageCodes(string $districtCode): array
    {
        return array_column($this->villages($districtCode), 'code');
    }

    public function provinceName(string $code): ?string
    {
        foreach ($this->provinces() as $province) {
            if ($province['code'] === $code) {
                return $province['name'];
            }
        }

        return null;
    }

    public function regencyName(string $provinceCode, string $regencyCode): ?string
    {
        foreach ($this->regencies($provinceCode) as $regency) {
            if ($regency['code'] === $regencyCode) {
                return $regency['name'];
            }
        }

        return null;
    }

    public function districtName(string $regencyCode, string $districtCode): ?string
    {
        foreach ($this->districts($regencyCode) as $district) {
            if ($district['code'] === $districtCode) {
                return $district['name'];
            }
        }

        return null;
    }

    public function villageName(string $districtCode, string $villageCode): ?string
    {
        foreach ($this->villages($districtCode) as $village) {
            if ($village['code'] === $villageCode) {
                return $village['name'];
            }
        }

        return null;
    }

    /** @return list<array{code: string, name: string, parent_code: string}> */
    private function allRegencies(): array
    {
        if ($this->regenciesCache !== null) {
            return $this->regenciesCache;
        }

        $regencies = $this->readJsonList('indonesia-regencies.json');
        foreach ($regencies as $regency) {
            abort_unless(is_string($regency['code'] ?? null)
                && is_string($regency['name'] ?? null)
                && is_string($regency['parent_code'] ?? null), 503, 'Data kabupaten/kota tidak valid.');
        }

        return $this->regenciesCache = $regencies;
    }

    /** @return list<array{code: string, name: string}> */
    private function children(string $filename, string $parentCode): array
    {
        $path = resource_path('data/'.$filename);
        abort_unless(File::exists($path), 503, 'Data wilayah belum tersedia.');

        $children = [];
        $handle = fopen($path, 'rb');
        abort_unless(is_resource($handle), 503, 'Data wilayah tidak dapat dibaca.');

        try {
            while (($line = fgets($handle)) !== false) {
                $region = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                abort_unless(is_array($region)
                    && is_string($region['code'] ?? null)
                    && is_string($region['name'] ?? null)
                    && is_string($region['parent_code'] ?? null), 503, 'Data wilayah tidak valid.');

                $order = strcmp($region['parent_code'], $parentCode);
                if ($order === 0) {
                    $children[] = ['code' => $region['code'], 'name' => $region['name']];
                } elseif ($order > 0) {
                    break;
                }
            }
        } finally {
            fclose($handle);
        }

        return $children;
    }

    /** @return list<array<string, mixed>> */
    private function readJsonList(string $filename): array
    {
        $path = resource_path('data/'.$filename);
        abort_unless(File::exists($path), 503, 'Data wilayah belum tersedia.');

        $decoded = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        abort_unless(is_array($decoded) && array_is_list($decoded), 503, 'Data wilayah tidak valid.');

        return $decoded;
    }
}
