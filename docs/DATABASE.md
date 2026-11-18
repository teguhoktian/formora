# Skema database Formora

Database utama adalah PostgreSQL. Migration Laravel mendefinisikan tabel produk
di bawah; tabel `users`, sesi, cache, queue, dan passkey berasal dari starter kit.

## Tabel produk

| Tabel | Isi | Identitas / relasi penting |
|---|---|---|
| `workspaces` | Ruang kerja | UUID; memiliki anggota dan form |
| `workspace_members` | Keanggotaan dan role awal | FK ke workspace dan user; unik per pasangan workspace/user |
| `forms` | Form, status draft/published/paused/archived, dan versi publik aktif | UUID; memiliki `workspace_id`; unik komposit `(workspace_id, id)` |
| `form_versions` | Snapshot schema form | UUID; JSONB; unik per `(form_id, version_number)` |
| `form_submissions` | Jawaban form dan status awal | UUID; JSONB; wajib menyimpan `form_version_id` |
| `audit_logs` | Metadata aktivitas | Aktor dan workspace opsional; tidak menyediakan kolom payload/jawaban |

`User` memiliki relasi keanggotaan dan workspace. `Workspace` memiliki relasi
anggota, form, dan audit. `Form` memiliki versi dan submission. Submission juga
memiliki relasi langsung ke workspace, form, dan versi yang dijawab.

## Integritas dan indeks

- UUID dibuat oleh Eloquent untuk workspace, form, versi, dan submission.
- `form_submissions (workspace_id, form_id)` mereferensikan pasangan workspace
  dan form yang sama.
- `form_submissions (form_id, form_version_id)` mereferensikan versi yang
  memang milik form tersebut. Kedua pasangan memakai foreign key komposit,
  sehingga database menolak submission lintas workspace atau versi yang salah.
- `forms (id, published_version_id)` mereferensikan versi milik form yang sama.
  PostgreSQL memakai foreign key komposit; SQLite test memakai trigger setara.
- Workspace tidak dapat dihapus selama masih punya form; form tidak dapat
  dihapus selama masih punya submission. Versi tanpa submission ikut terhapus
  saat form dihapus. Penghapusan anggota mengikuti FK dan constraint unik.
- Status role, form, dan submission dibatasi oleh kolom enum database.
- Indeks mendukung lookup anggota per user, form per workspace/status, submission
  per workspace/form/status, dan audit per workspace/aktor/waktu.

## Data sensitif dan audit

`response_data` dan schema disimpan sebagai JSONB pada tabel terpisah. Jangan
menyalin isi jawaban, NIK, NPWP, nomor rekening, password, atau isi file ke audit
log. Audit hanya menyimpan event, aktor/workspace, serta tipe dan ID subjek.
Penerapan retention, deletion workflow, dan kebijakan akses audit masih menjadi
pekerjaan hardening sebelum production.

Kolom `is_published` dan `published_at` menandai snapshot historis. Pointer
`published_version_id` memilih versi aktif; status `paused` mempertahankan pointer,
sedangkan unpublish mengosongkannya tanpa menghapus versi. Versi published tidak
dapat diubah atau dihapus; model guard dan database trigger menolak perubahan.

## Menjalankan migration

Pastikan `.env` menunjuk ke database PostgreSQL yang memang dimaksud, lalu
jalankan `php artisan migrate`. Migration Tahap 2 bersifat aditif. Jangan gunakan
`migrate:fresh` atau `db:wipe` pada database yang berisi data. Skema/constraint
diuji oleh `tests/Feature/Database/FormoraSchemaTest.php`.

Seed standar sengaja tidak membuat pengguna atau submission contoh. Gunakan
factory hanya di test atau lingkungan terisolasi.
