<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_platform_moderator')->default(false);
        });

        Schema::create('form_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 20);
            $table->string('title', 160);
            $table->string('slug', 64)->unique();
            $table->text('description')->nullable();
            $table->string('category', 80);
            $table->string('visibility', 20)->default('private');
            $table->string('status', 20)->default('draft');
            $table->text('moderation_note')->nullable();
            $table->timestampTz('moderated_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'id']);
            $table->index(['kind', 'visibility', 'status', 'category']);
            $table->index(['workspace_id', 'status', 'visibility']);
        });

        Schema::create('form_template_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version_number');
            $table->jsonb('schema');
            $table->timestamps();

            $table->unique(['template_id', 'version_number']);
            $table->unique(['template_id', 'id']);
        });

        $field = static fn (string $type, string $name, string $label, bool $required = false, array $settings = []): array => [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'name' => $name,
            'label' => $label,
            'required' => $required,
            'help_text' => null,
            'default_value' => null,
            'settings' => $settings,
            'validation' => [],
        ];
        $schema = static fn (string $pageTitle, array $fields): array => [
            'schema_version' => 1,
            'pages' => [[
                'id' => (string) Str::uuid(),
                'title' => $pageTitle,
                'description' => null,
                'fields' => $fields,
            ]],
            'settings' => ['submit_label' => 'Kirim', 'success_message' => 'Terima kasih, data berhasil dikirim.'],
        ];
        $now = now();
        $officialTemplates = [
            [
                'title' => 'Pendaftaran kegiatan', 'slug' => 'official-pendaftaran-kegiatan', 'category' => 'Acara',
                'description' => 'Kumpulkan informasi dasar peserta kegiatan.',
                'schema' => $schema('Data peserta', [
                    $field('text', 'nama_lengkap', 'Nama lengkap', true),
                    $field('email', 'email', 'Alamat email', true),
                    $field('phone', 'nomor_telepon', 'Nomor telepon'),
                    $field('text', 'instansi', 'Instansi'),
                ]),
            ],
            [
                'title' => 'Survei kepuasan', 'slug' => 'official-survei-kepuasan', 'category' => 'Survei',
                'description' => 'Minta penilaian dan masukan singkat dari pelanggan.',
                'schema' => $schema('Penilaian layanan', [
                    $field('select', 'penilaian', 'Bagaimana pengalaman Anda?', true, ['options' => [
                        ['label' => 'Sangat baik', 'value' => 'sangat_baik'],
                        ['label' => 'Baik', 'value' => 'baik'],
                        ['label' => 'Cukup', 'value' => 'cukup'],
                        ['label' => 'Perlu ditingkatkan', 'value' => 'perlu_ditingkatkan'],
                    ]]),
                    $field('textarea', 'masukan', 'Masukan tambahan'),
                ]),
            ],
            [
                'title' => 'Permintaan informasi', 'slug' => 'official-permintaan-informasi', 'category' => 'Bisnis',
                'description' => 'Catat permintaan informasi dari calon pelanggan.',
                'schema' => $schema('Informasi pemohon', [
                    $field('text', 'nama', 'Nama', true),
                    $field('email', 'email', 'Alamat email', true),
                    $field('text', 'perusahaan', 'Perusahaan'),
                    $field('textarea', 'kebutuhan', 'Informasi yang dibutuhkan', true),
                ]),
            ],
        ];

        foreach ($officialTemplates as $templateData) {
            $templateId = (string) Str::uuid();
            $versionId = (string) Str::uuid();
            DB::table('form_templates')->insert([
                'id' => $templateId,
                'workspace_id' => null,
                'created_by' => null,
                'moderated_by' => null,
                'kind' => 'official',
                'title' => $templateData['title'],
                'slug' => $templateData['slug'],
                'description' => $templateData['description'],
                'category' => $templateData['category'],
                'visibility' => 'public',
                'status' => 'published',
                'moderation_note' => null,
                'moderated_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('form_template_versions')->insert([
                'id' => $versionId,
                'template_id' => $templateId,
                'created_by' => null,
                'version_number' => 1,
                'schema' => json_encode($templateData['schema'], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('form_template_versions');
        Schema::dropIfExists('form_templates');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_platform_moderator');
        });
    }
};
