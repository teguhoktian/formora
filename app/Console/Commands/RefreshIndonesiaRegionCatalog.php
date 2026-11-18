<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RefreshIndonesiaRegionCatalog extends Command
{
    protected $signature = 'formora:regions:refresh';

    protected $description = 'Refresh snapshot provinsi sampai desa/kelurahan dari data wilayah Indonesia.';

    public function handle(): int
    {
        try {
            $response = Http::timeout(60)->retry(2, 500)
                ->get('https://raw.githubusercontent.com/cahyadsn/wilayah/master/db/wilayah.sql')
                ->throw();
            $sql = $response->body();
            preg_match_all("/\\('([^']+)',\\s*'((?:[^'\\\\]|\\\\.|'')*)'\\)/", $sql, $matches);
            if (count($matches[0]) < 90000) {
                throw new RuntimeException('Data wilayah tidak lengkap atau format sumber berubah.');
            }

            $provinces = [];
            $regencies = [];
            $districts = [];
            $villages = [];
            $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
            foreach ($matches[1] as $index => $code) {
                $name = str_replace("''", "'", $matches[2][$index]);
                $segments = explode('.', $code);
                $level = count($segments);
                if ($level > 4 || $name === '') {
                    throw new RuntimeException('Kode atau nama wilayah dari sumber tidak valid.');
                }

                $counts[$level]++;
                if ($level === 1) {
                    $provinces[$code] = ['code' => $code, 'name' => $name];
                } elseif ($level === 2) {
                    $provinceCode = $segments[0];
                    if (! isset($provinces[$provinceCode])) {
                        throw new RuntimeException('Data kabupaten/kota tidak memiliki provinsi induk.');
                    }
                    $regencies[$code] = ['code' => $code, 'name' => $name, 'parent_code' => $provinceCode];
                } elseif ($level === 3) {
                    $regencyCode = implode('.', array_slice($segments, 0, 2));
                    if (! isset($regencies[$regencyCode])) {
                        throw new RuntimeException('Data kecamatan tidak memiliki kabupaten/kota induk.');
                    }
                    $districts[$code] = ['code' => $code, 'name' => $name, 'parent_code' => $regencyCode];
                } else {
                    $districtCode = implode('.', array_slice($segments, 0, 3));
                    if (! isset($districts[$districtCode])) {
                        throw new RuntimeException('Data desa/kelurahan tidak memiliki kecamatan induk.');
                    }
                    $villages[] = ['code' => $code, 'name' => $name, 'parent_code' => $districtCode];
                }
            }

            if ($counts[1] !== 38 || $counts[2] < 500 || $counts[3] < 7000 || $counts[4] < 80000) {
                throw new RuntimeException('Jumlah data wilayah dari sumber tidak sesuai; snapshot lama dipertahankan.');
            }

            ksort($regencies, SORT_STRING);
            ksort($districts, SORT_STRING);
            usort($villages, fn (array $left, array $right): int => strcmp($left['code'], $right['code']));

            preg_match('/last edit:\\s*(\\d{4}-\\d{2}-\\d{2})/i', $sql, $updatedAt);
            $metadata = [
                'provider' => 'cahyadsn/wilayah; data source Kepmendagri No. 300.2.2-2138 Tahun 2025',
                'url' => 'https://github.com/cahyadsn/wilayah/blob/master/db/wilayah.sql',
                'source_updated_at' => $updatedAt[1] ?? null,
                'refreshed_at' => now()->toDateString(),
            ];
            $directory = resource_path('data');
            File::ensureDirectoryExists($directory);
            $snapshots = [
                'indonesia-region-source.json' => $metadata,
                'indonesia-provinces.json' => array_values($provinces),
                'indonesia-regencies.json' => array_values($regencies),
                'indonesia-districts.jsonl' => implode("\n", array_map(
                    fn (array $district): string => json_encode($district, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                    array_values($districts),
                ))."\n",
                'indonesia-villages.jsonl' => implode("\n", array_map(
                    fn (array $village): string => json_encode($village, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                    $villages,
                ))."\n",
            ];
            $temporaryFiles = [];
            foreach ($snapshots as $filename => $snapshot) {
                $path = $directory.'/'.$filename;
                $temporaryPath = $path.'.'.Str::uuid().'.tmp';
                $content = is_string($snapshot)
                    ? $snapshot
                    : json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
                File::put($temporaryPath, $content);
                $temporaryFiles[$temporaryPath] = $path;
            }
            foreach ($temporaryFiles as $temporaryPath => $path) {
                File::move($temporaryPath, $path);
            }

            $this->info(sprintf(
                'Data diperbarui: %d provinsi, %d kabupaten/kota, %d kecamatan, %d desa/kelurahan (sumber: %s).',
                ...[...array_values($counts), $metadata['source_updated_at'] ?? 'tanggal tidak tersedia'],
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Pembaruan data wilayah gagal: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
