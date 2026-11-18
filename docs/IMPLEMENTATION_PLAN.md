# FORMORA — Master Development Plan untuk Codex AI Agent

> **Tujuan dokumen:** menjadi instruksi utama Codex AI Agent untuk membangun Formora secara bertahap dari development lokal sampai MVP siap diuji dan dipersiapkan untuk production.
>
> **Cara kerja:** Codex harus membaca dokumen ini sebelum mengubah kode, mengerjakan satu tahap pada satu waktu, menjalankan pengujian, melaporkan hasil, lalu berhenti menunggu instruksi pengguna untuk tahap berikutnya.
>
> **Prinsip utama:** jangan membangun semuanya sekaligus. Prioritaskan fondasi yang benar, keamanan data, tenant isolation, dan kode yang mudah dirawat.

---

## 0. Identitas dan keputusan produk

### 0.1 Produk

Nama kerja: **Formora**.

Formora adalah platform **Smart Form & Document** untuk membuat formulir digital, mengumpulkan jawaban, mengelola submission, menggunakan template, dan pada tahap berikutnya menghasilkan dokumen serta menjalankan approval workflow.

Target pengguna mencakup individu, UMKM, bisnis, perusahaan, bank/koperasi, sekolah, dan instansi pemerintah.

Formora bukan sekadar clone Google Forms. Pembeda utamanya:

- Advanced form builder seperti Typeform/Jotform.
- Smart Fields Indonesia: NIK, NPWP, rekening bank, nominal Rupiah, alamat bertingkat, NIP, NISN, NUPTK, dan data Indonesia lainnya.
- Template resmi platform dan template komunitas.
- Pengelolaan submission, status, ekspor, dan audit.
- Tahap lanjutan: document automation, approval, workspace team, API/webhook, billing, dan AI form generator.

Nama Formora masih nama kerja. Jangan mengerjakan pembelian domain atau klaim merek dagang.

### 0.2 Stack yang ditetapkan

| Area | Keputusan |
|---|---|
| Backend | Laravel |
| PHP | 8.3 |
| Database | PostgreSQL |
| UI | Blade + Livewire |
| Interaksi ringan | Alpine.js yang terintegrasi dengan Livewire |
| CSS | Tailwind CSS |
| Build asset | Vite |
| Drag and drop | SortableJS |
| Dashboard | Custom Blade + Livewire |
| Arsitektur | Modular monolith |
| Queue awal | Laravel database queue |
| Cache awal | Database/file sesuai kebutuhan |
| File | Local saat development; object storage eksternal saat production |
| Deployment | VPS Linux, Nginx, PHP-FPM; dikerjakan setelah local MVP stabil |

**Jangan gunakan sebagai arsitektur utama:** Next.js, Supabase, Vercel, Filament untuk dashboard end-user, Redis, Docker, microservices, Elasticsearch, atau Meilisearch. Jangan menambah layanan tersebut tanpa alasan teknis yang terukur dan persetujuan pengguna.

Filament boleh dipertimbangkan kelak hanya untuk internal platform admin, bukan sebagai UI utama pengguna Formora.

### 0.3 Lingkungan development

Utamakan development pada **WSL Ubuntu** jika tersedia, agar konsisten dengan target VPS Linux. Jika pengguna memilih Windows native, sesuaikan perintah tanpa mengubah arsitektur.

Default direktori project yang disarankan:

```bash
~/projects/formora
```

Jangan mengasumsikan direktori ini sudah ada. Periksa dahulu.

---

## 1. Aturan wajib Codex AI Agent

### 1.1 Mode kerja

Codex harus:

1. Membaca file ini dari awal sebelum mulai.
2. Memeriksa kondisi repository dan lingkungan sebelum mengubah apa pun.
3. Mengerjakan **hanya satu tahap** dalam satu sesi kerja.
4. Membuat rencana singkat sebelum perubahan yang melibatkan banyak file.
5. Mengimplementasikan fitur secara lengkap, bukan membuat placeholder yang tidak berfungsi.
6. Menjalankan formatter, static check yang tersedia, dan test relevan setelah perubahan.
7. Memperbaiki kegagalan yang disebabkan oleh perubahan sendiri.
8. Memperbarui `docs/PROGRESS.md` setelah setiap tahap selesai.
9. Menyebutkan file yang dibuat/diubah, perintah test, hasil test, dan risiko yang masih tersisa.
10. Berhenti setelah tahap selesai dan menunggu persetujuan pengguna untuk tahap berikutnya.

**Dilarang melanjutkan otomatis ke tahap berikutnya**, walaupun tahap sekarang selesai dengan sukses.

### 1.2 Jangan merusak pekerjaan yang sudah ada

Sebelum bekerja:

```bash
pwd
git status --short
git branch --show-current
git log -1 --oneline
```

Aturan:

- Jangan menghapus atau menimpa perubahan pengguna yang belum di-commit.
- Jangan menjalankan `git reset --hard`, `git clean -fd`, atau perintah destruktif.
- Jangan mengganti file konfigurasi secara massal tanpa membaca isinya.
- Jangan menghapus database, tabel, migration, atau file pengguna.
- Jangan menjalankan `migrate:fresh`, `db:wipe`, atau seeder destruktif pada database yang berisi data.
- Jangan melakukan commit atau push kecuali diminta.
- Jika menemukan perubahan yang tidak dipahami, berhenti dan tanyakan.

### 1.3 Kualitas implementasi

- Gunakan Laravel conventions dan dependency injection.
- Pisahkan UI, application action, domain logic, dan persistence secara wajar.
- Hindari overengineering dan abstraksi yang belum dibutuhkan.
- Hindari controller/Livewire component yang terlalu besar.
- Gunakan Form Request atau validasi Livewire yang sesuai.
- Gunakan Policies/Gates untuk otorisasi server-side.
- Gunakan database transaction untuk operasi multi-tabel yang harus atomik.
- Gunakan UUID untuk entitas bisnis utama yang diekspos ke luar.
- Gunakan foreign key, unique constraint, index, dan check constraint bila sesuai.
- Semua tanggal/waktu disimpan konsisten; tampilkan sesuai locale Indonesia.
- Gunakan Bahasa Indonesia untuk UI pengguna. Kode, nama class, tabel, dan variabel memakai bahasa Inggris yang konsisten.
- Jangan menaruh business logic penting hanya di JavaScript.
- Jangan membuat data palsu untuk menyamarkan fitur yang belum tersedia.
- Jangan mengklaim integrasi eksternal sudah berfungsi jika belum diuji.

### 1.4 Keamanan dan privasi

Formora berpotensi memproses NIK, NPWP, nomor rekening, dokumen identitas, dan data pribadi lainnya.

Wajib:

- Tenant/workspace isolation di server-side.
- Policy untuk setiap operasi sensitif.
- Validasi backend.
- Masking data sensitif pada tampilan dan log.
- Jangan log payload submission mentah.
- Jangan memasukkan rahasia, token, password, API key, atau data pribadi ke source control.
- Upload harus membatasi ukuran, MIME/type, ekstensi, dan hak akses.
- File privat tidak boleh dapat diakses hanya dengan menebak URL.
- Jangan mengklaim format NIK/NPWP atau nomor rekening sebagai verifikasi resmi identitas.
- Jangan menganggap pola nomor rekening semua bank sama; aturan harus configurable.
- Catat aktivitas audit tanpa menyimpan nilai sensitif.
- Sediakan dasar consent, retention, dan deletion sebelum production.
- Uji akses lintas workspace.

Jangan mengimplementasikan enkripsi custom. Gunakan fasilitas Laravel dan mekanisme penyimpanan yang teruji.

---

## 2. Instruksi hemat token dan efisiensi Codex

**Tempelkan aturan berikut ke setiap sesi Codex** bila agent tidak otomatis membaca file ini.

### 2.1 Prompt ringkas standar

```text
Baca AGENTS.md dan docs/IMPLEMENTATION_PLAN.md.
Baca docs/PROGRESS.md untuk mengetahui tahap aktif.
Kerjakan hanya tahap [NAMA/NOMOR TAHAP] yang belum selesai.
Sebelum coding, periksa git status dan file terkait.
Jangan mengulang audit atau membaca seluruh repository tanpa alasan.
Buat perubahan sekecil mungkin tetapi lengkap.
Jangan menambah dependency/arsitektur tanpa kebutuhan dan persetujuan.
Jalankan test yang relevan, perbaiki error akibat perubahanmu.
Perbarui docs/PROGRESS.md.
Laporkan ringkas: perubahan, file, test dan hasil, risiko, serta langkah manual.
Berhenti setelah tahap ini; jangan lanjut ke tahap berikutnya.
```

### 2.2 Aturan penggunaan konteks

Codex harus:

- Membaca file yang relevan saja; jangan mencetak seluruh isi repository.
- Gunakan `rg`/search untuk menemukan class, route, migration, atau komponen yang diperlukan.
- Membaca file utuh hanya bila konteks memang diperlukan.
- Jangan mengulang penjelasan requirement yang sudah tercatat di dokumen.
- Jangan menghasilkan dokumentasi panjang untuk perubahan kecil.
- Jangan membuat banyak alternatif implementasi jika keputusan sudah ditetapkan.
- Jangan menambahkan dependency untuk fungsi sederhana yang bisa ditangani Laravel.
- Jangan menjalankan seluruh test suite pada setiap perubahan kecil jika test terarah cukup; tetap jalankan full suite pada checkpoint tahap.
- Jangan mengulang test yang sama tanpa perubahan kode atau alasan.
- Jika test gagal karena environment, bedakan dari regression kode dan laporkan jelas.
- Jangan melakukan refactor di luar scope tahap.
- Gunakan output terminal yang ringkas; hindari dump file, vendor, node_modules, atau log besar.

### 2.3 Batas perubahan

- Satu tahap = satu tujuan yang bisa diverifikasi.
- Jika tahap membutuhkan sub-tahap, kerjakan berurutan dan catat.
- Jika menemukan masalah di luar scope, catat sebagai backlog.
- Jika requirement ambigu dan berdampak pada skema data/keamanan, tanyakan sebelum memilih.
- Jangan menebak kredensial, domain, nama perusahaan, atau aturan legal.

### 2.4 Format laporan akhir setiap tahap

```text
TAHAP:
STATUS: selesai / terblokir / perlu keputusan

IMPLEMENTASI:
- ...

FILE:
- ...

VALIDASI:
- Perintah:
- Hasil:

MANUAL CHECK:
- ...

RISIKO / BACKLOG:
- ...

LANGKAH BERIKUTNYA:
- Tahap berikutnya (belum dikerjakan).
```

---

## 3. Dokumen proyek yang harus dipelihara

Codex harus membuat dan menjaga file berikut:

```text
AGENTS.md
docs/
  IMPLEMENTATION_PLAN.md
  PROGRESS.md
  ARCHITECTURE.md
  DATABASE.md
  SECURITY.md
  UI_GUIDELINES.md
```

Kegunaan:

- `AGENTS.md`: aturan singkat yang dibaca Codex otomatis.
- `IMPLEMENTATION_PLAN.md`: dokumen rencana ini.
- `PROGRESS.md`: status tahap, keputusan, dan backlog.
- `ARCHITECTURE.md`: gambaran arsitektur aktual, bukan angan-angan.
- `DATABASE.md`: tabel, relasi, constraint, dan keputusan data.
- `SECURITY.md`: threat model ringkas, tenant isolation, data sensitif, upload, dan audit.
- `UI_GUIDELINES.md`: standar visual dan interaksi.

Jangan menyalin dokumen ini berulang kali ke file lain. Buat referensi silang agar hemat konteks.

### 3.1 Template AGENTS.md

Buat `AGENTS.md` di root repository dengan isi ringkas berikut:

```markdown
# Instructions for Codex

1. Read `docs/IMPLEMENTATION_PLAN.md` and `docs/PROGRESS.md` before work.
2. Work on only one approved stage at a time.
3. Inspect `git status` before editing. Never discard user changes.
4. Follow the selected stack: Laravel, PHP 8.3, PostgreSQL, Livewire, Blade,
   Alpine, Tailwind, Vite, and SortableJS.
5. Keep the application a modular monolith. No Next.js, Supabase, Vercel,
   Redis, Docker, microservices, or Filament end-user dashboard unless approved.
6. Enforce workspace isolation and server-side authorization for every
   business operation.
7. Never log raw personal/sensitive submission values.
8. Add/update automated tests for behavior and security boundaries.
9. Run relevant tests and report exact commands/results.
10. Update `docs/PROGRESS.md` after each approved stage.
11. Do not proceed to the next stage without user approval.
12. Do not run destructive database or Git commands.
13. Keep responses concise; inspect only relevant files and avoid repeating
    repository context.
```

---

## 4. Tahap 0 — Audit lingkungan lokal

**Tujuan:** mengetahui kondisi laptop/WSL sebelum instalasi.

### Tugas Codex

1. Periksa OS, PHP, Composer, PostgreSQL client/server, Node, npm, Git.
2. Periksa apakah direktori project sudah ada.
3. Jika direktori project sudah ada dan berisi file, jangan menimpanya.
4. Jangan menginstal paket sistem tanpa izin.
5. Buat laporan kebutuhan yang belum terpenuhi.

Perintah pemeriksaan:

```bash
uname -a
cat /etc/os-release
php -v
composer --version
psql --version
node -v
npm -v
git --version
```

Jika suatu command tidak tersedia, laporkan sebagai missing; jangan langsung instal.

### Kriteria selesai

- Versi lingkungan tercatat.
- Lokasi project dipastikan.
- Tidak ada file pengguna yang tertimpa.
- Daftar instalasi yang diperlukan tersedia.

**Berhenti setelah tahap 0.**

---

## 5. Tahap 1 — Bootstrap Laravel

**Tujuan:** membuat aplikasi Laravel bersih dan dapat dijalankan lokal.

### Tugas

1. Buat project Laravel pada direktori yang disetujui.
2. Pilih versi Laravel stabil yang kompatibel dengan PHP 8.3. Verifikasi requirement Composer sebelum instalasi; jangan memaksakan versi yang tidak kompatibel.
3. Pasang Livewire stable yang kompatibel dengan Laravel terpilih.
4. Siapkan Vite dan Tailwind sesuai starter kit/dokumentasi versi yang benar.
5. Pasang starter autentikasi yang paling ringan dan kompatibel; gunakan Livewire starter kit bila cocok.
6. Pastikan Alpine tidak dimuat dua kali.
7. Buat halaman awal Formora sederhana.
8. Buat `.env.example` yang aman dan dokumentasi setup lokal.
9. Buat test smoke test aplikasi.

### Batasan

- Jangan pasang Filament.
- Jangan pasang Redis.
- Jangan pasang package permission sebelum desain role/workspace siap.
- Jangan membangun builder di tahap ini.
- Jangan menambahkan integrasi pembayaran atau object storage.

### Kriteria selesai

- `composer install` berhasil.
- `npm install` dan `npm run build` berhasil.
- `php artisan test` berhasil.
- Aplikasi dapat dibuka lokal.
- Halaman awal Formora tampil.
- Versi dependency tercatat.

**Berhenti setelah tahap 1.**

---

## 6. Tahap 2 — Fondasi database dan model

**Tujuan:** menyiapkan model data minimal dan migration yang aman.

### Tabel tahap ini

1. `users` — migration Laravel.
2. `workspaces`.
3. `workspace_members`.
4. `forms`.
5. `form_versions`.
6. `form_submissions`.
7. `audit_logs`.

### Aturan skema

- PostgreSQL sebagai database utama.
- UUID untuk workspace, form, version, submission.
- JSONB untuk schema form dan jawaban submission.
- `workspace_id` menjadi batas tenancy.
- `form_submissions` wajib menyimpan `form_version_id`.
- Submission harus mengacu pada versi milik form yang sama; implementasikan validasi kuat dan constraint database bila memungkinkan.
- Tambahkan index untuk query umum.
- Gunakan foreign key dan aturan delete yang disengaja.
- Hindari menyimpan salinan data pribadi yang tidak diperlukan.
- Audit log tidak boleh menyimpan nilai NIK, NPWP, rekening, password, atau file pribadi.

### Catatan desain

- `forms` mempunyai status draft/published/paused/archived.
- `form_versions` menyimpan snapshot schema. Versi yang sudah dipublikasikan tidak boleh diedit; perubahan harus menghasilkan versi baru.
- `form_submissions` menyimpan status dan `response_data`.
- Jangan membuat seluruh tabel fitur masa depan sekarang.
- Pertimbangkan composite foreign key PostgreSQL untuk memastikan pasangan `(form_id, form_version_id)` valid. Jika implementasi migration Laravel memerlukan raw SQL, beri komentar dan test; jangan melemahkan integritas data demi kemudahan.

### Tugas

- Buat migrations.
- Buat Eloquent models dan relationships.
- Buat factory/seed data minimal untuk testing.
- Buat tests relasi dan constraint.
- Buat `docs/DATABASE.md`.

### Kriteria selesai

- Migration berhasil pada database development kosong.
- Rollback tahap terkontrol berhasil.
- Model relationships bekerja.
- Test constraint dan referensi versi berhasil.
- Tidak ada data contoh sensitif.

**Berhenti setelah tahap 2.**

---

## 7. Tahap 3 — Autentikasi dan workspace

**Tujuan:** user dapat mendaftar/login dan bekerja dalam workspace.

### Fitur

- Register, login, logout, reset password sesuai starter kit.
- Profil dasar pengguna.
- Pembuatan workspace personal otomatis saat registrasi.
- Pengguna dapat membuat workspace tambahan jika diizinkan.
- Anggota workspace.
- Peran awal: owner, admin, member, viewer.
- Pemilihan workspace aktif.
- Middleware/service untuk konteks workspace.
- Policy untuk workspace dan anggota.

### Security tests wajib

- User A tidak dapat melihat workspace User B.
- User A tidak dapat membaca form milik workspace User B.
- User tidak dapat mengubah `workspace_id` melalui request untuk mengambil alih data.
- Viewer tidak dapat mengubah data.
- Member tidak dapat mengelola anggota jika tidak diizinkan.
- Owner tidak dapat dihapus sebagai anggota tanpa transfer ownership yang valid.

### Kriteria selesai

- Login/register berjalan.
- Workspace personal dibuat.
- User dapat memilih workspace yang menjadi anggotanya.
- Semua test akses lintas workspace lulus.

**Berhenti setelah tahap 3.**

---

## 8. Tahap 4 — Dashboard custom

**Tujuan:** membuat dashboard end-user Formora tanpa Filament.

### Navigasi awal

- Overview
- My Forms
- Submissions
- Template Library
- Smart Fields
- Analytics
- Workspace
- Settings

Menu yang belum berfungsi harus ditandai "Segera hadir" atau tidak ditampilkan. Jangan membuat tombol palsu.

### Arah desain

- Modern SaaS, profesional, bersih, corporate.
- Responsive desktop/tablet/mobile.
- Sidebar desktop, navigasi mobile yang jelas.
- Header berisi nama workspace dan user menu.
- Gunakan komponen Blade reusable.
- Gunakan state Livewire untuk data dinamis.
- Empty state yang informatif.
- Loading state dan error state.
- Kontras, fokus keyboard, label input, dan aksesibilitas dasar.
- Bahasa Indonesia untuk UI.

### Overview

- Jumlah form.
- Form published.
- Total submission.
- Submission terbaru.
- Aktivitas terakhir.
- CTA buat form baru.

Angka harus berasal dari database, bukan hardcoded.

### Kriteria selesai

- Layout konsisten di seluruh halaman.
- Navigasi berfungsi.
- Workspace aktif terlihat.
- Dashboard memakai data real.
- Responsive.
- Test authorization halaman.

**Berhenti setelah tahap 4.**

---

## 9. Tahap 5 — Form Builder: fondasi schema

**Tujuan:** membangun mesin form sebelum mempercantik seluruh UI.

### Desain schema awal

Gunakan JSONB dengan struktur berversi. Contoh:

```json
{
  "schema_version": 1,
  "pages": [
    {
      "id": "stable-page-id",
      "title": "Data Pemohon",
      "description": null,
      "fields": [
        {
          "id": "stable-field-id",
          "type": "text",
          "name": "nama_lengkap",
          "label": "Nama Lengkap",
          "required": true,
          "help_text": null,
          "default_value": null,
          "settings": {},
          "validation": {}
        }
      ]
    }
  ],
  "settings": {
    "submit_label": "Kirim",
    "success_message": "Terima kasih, data berhasil dikirim."
  }
}
```

ID field/page harus stabil. Jangan memakai posisi array sebagai identitas.

### Komponen builder

Desktop:

- Toolbox kiri.
- Canvas tengah.
- Inspector kanan.
- Tab Build, Design, Logic, Settings, Preview.

Fitur dasar:

- Buat form.
- Tambah/hapus field.
- Ubah label, name, required, help text.
- Susun ulang field dengan SortableJS.
- Tambah/hapus page.
- Duplikasi field.
- Autosave dengan debounce.
- Indikator saved/saving/error.
- Preview.
- Undo/redo setelah desain state jelas.

### Aturan teknis

- Livewire mengelola state dan persistensi.
- Alpine untuk interaksi browser ringan.
- SortableJS hanya mengatur drag/drop.
- Integrasikan SortableJS dengan Livewire secara hati-hati; gunakan `wire:ignore` hanya pada area yang benar-benar dikelola library dan sinkronkan perubahan ke Livewire.
- Jangan mengirim seluruh schema pada setiap pointer movement.
- Simpan perubahan setelah interaksi selesai/debounce.
- Validasi schema di server.
- Jangan mengizinkan field type arbitrary dari client.

### Field type awal

- text
- textarea
- email
- phone
- number
- date
- select
- radio
- checkbox
- file

Smart Fields Indonesia dibuat di tahap tersendiri.

### Kriteria selesai

- Builder dapat membuat dan mengedit form.
- Field dapat disusun ulang.
- Autosave tidak kehilangan perubahan.
- Refresh halaman memuat schema terakhir.
- Test schema validation dan workspace isolation lulus.

**Berhenti setelah tahap 5.**

---

## 10. Tahap 6 — Versioning dan publish

**Tujuan:** membuat versi form yang aman untuk submission.

### Aturan

- Draft aktif dapat diedit.
- Publish membuat snapshot versi baru.
- Published version immutable.
- Edit setelah publish menghasilkan draft/version baru.
- Form memiliki pointer ke published version aktif.
- Unpublish/pause tidak menghapus versi historis.
- Submission selalu menunjuk ke versi yang dipakai saat responden mengisi.

### Kriteria selesai

- Publish menghasilkan version baru.
- Versi lama tidak berubah.
- Submission historis tetap terhubung ke versi lama.
- Test concurrency dan publish permission tersedia.

**Berhenti setelah tahap 6.**

---

## 11. Tahap 7 — Public form renderer dan submission

**Tujuan:** responden dapat mengisi form publik tanpa akun.

### Fitur

- Public URL dengan slug unik.
- Render berdasarkan published version.
- Multi-page form.
- Validasi backend berdasarkan schema.
- Required dan field validation.
- Submit dan confirmation message.
- Reference number unik.
- Status submission.
- Rate limiting dasar.
- Honeypot atau proteksi spam yang proporsional.
- Upload file privat dengan validasi.
- Error message yang mudah dipahami.

### Keamanan

- Responden publik hanya dapat membaca form published yang memang public.
- Responden tidak dapat membaca submission lain.
- Endpoint publik tidak mengembalikan schema draft.
- Jangan mempercayai hidden input atau field `workspace_id` dari browser.
- Validasi MIME/ukuran file di server.
- Jangan menampilkan data sensitif di URL, log, atau error detail.

### Kriteria selesai

- Form published dapat dibuka tanpa login.
- Submission tersimpan dengan version yang benar.
- Invalid payload ditolak backend.
- Submission tidak bisa dibaca publik.
- Rate limit bekerja.
- Test akses publik lulus.

**Berhenti setelah tahap 7.**

---

## 12. Tahap 8 — Submission management

**Tujuan:** pemilik form dapat mengelola respons.

### Fitur

- Daftar submission per form.
- Pagination server-side.
- Search dan filter.
- Detail submission.
- Status new, in_review, approved, rejected, completed.
- Catatan internal jika dibutuhkan.
- Export CSV/XLSX.
- Audit event.
- Masking field sensitif.
- Permission untuk melihat/mengunduh data.

### Kinerja

- Jangan mengambil semua submission sekaligus.
- Gunakan pagination.
- Batasi kolom yang di-query.
- Export besar menggunakan queue, bukan request web yang panjang.
- Jangan memasukkan isi sensitif ke log export.

### Kriteria selesai

- Daftar dan detail berjalan.
- Filter/search benar.
- Export sesuai filter.
- Hak akses diuji.
- Export besar tidak memblokir request web.

**Berhenti setelah tahap 8.**

---

## 13. Tahap 9 — Smart Fields Indonesia

**Tujuan:** menyediakan field khusus untuk kebutuhan Indonesia.

### Field awal

1. NIK.
2. NPWP.
3. Tanggal lahir.
4. Nomor rekening bank.
5. Nama bank.
6. Nominal Rupiah.
7. Nomor telepon Indonesia.
8. Alamat bertingkat.
9. NIP.
10. NISN.
11. NUPTK.
12. NIM.

### Arsitektur

Gunakan registry/definition yang konsisten untuk:

- Identitas field.
- Label.
- Default settings.
- Builder control.
- Renderer.
- Backend validation.
- Formatting/masking.
- Dokumentasi.

Jangan membuat validasi berbeda-beda di builder dan renderer.

### Aturan khusus

- Validasi format bukan verifikasi resmi ke Dukcapil, DJP, bank, atau lembaga lain.
- Jangan membuat klaim validasi identitas resmi tanpa integrasi dan izin yang sah.
- Format nomor rekening berbeda antarbank dan dapat berubah.
- Data referensi bank/daerah harus punya sumber, tanggal pembaruan, dan mekanisme update.
- NIK dan nomor rekening harus dimasking pada tampilan yang tidak memerlukan nilai penuh.

### Kriteria selesai

- Registry berjalan.
- Field dapat dipakai di builder dan renderer.
- Validasi backend teruji.
- Masking bekerja.
- Tidak ada klaim verifikasi eksternal palsu.

**Berhenti setelah tahap 9.**

---

## 14. Tahap 10 — Template Library

**Tujuan:** pengguna dapat menggunakan ulang form.

### Jenis template

- Official: diterbitkan platform.
- My Templates: milik workspace/pengguna.
- Community: dibagikan komunitas setelah moderasi.

### Fitur awal

- Kategori.
- Pencarian.
- Preview.
- Simpan sebagai template.
- Duplicate/use template.
- Copy menghasilkan form baru dan schema snapshot independen.
- Visibilitas private/unlisted/public.
- Moderasi untuk community.
- Versioning template dasar.

### Kriteria selesai

- Template official tampil.
- User dapat menyimpan template.
- User dapat menggunakan template.
- Copy tidak mengubah template sumber.
- Permission dan visibilitas diuji.

**Berhenti setelah tahap 10.**

---

## 15. Tahap 11 — Hardening MVP

**Tujuan:** memastikan MVP aman, stabil, dan siap diuji pengguna terbatas.

### Cakupan

- Security review.
- Workspace isolation tests.
- Authorization matrix.
- CSRF/session/cookie settings.
- Rate limiting.
- Upload validation.
- Error handling.
- Audit log.
- Database backup/restore rehearsal.
- Log rotation.
- Performance profiling.
- Pagination dan query review.
- Mobile usability.
- Accessibility dasar.
- Data retention/deletion design.
- Privacy notice dan consent flow yang sesuai.
- Dokumentasi instalasi dan deployment.

### Kriteria MVP

Pengguna dapat:

1. Register/login.
2. Membuat workspace.
3. Membuat form.
4. Menambah dan mengatur field.
5. Preview dan publish form.
6. Membagikan public link.
7. Menerima submission.
8. Melihat dan mengelola submission.
9. Export data.
10. Menggunakan template.
11. Bekerja tanpa bisa mengakses workspace pengguna lain.

**Berhenti setelah tahap 11.**

---

## 16. Tahap lanjutan — jangan dikerjakan sebelum MVP disetujui

Fitur berikut adalah backlog setelah MVP:

- PDF/document automation.
- Template surat dan kuitansi.
- Approval workflow berjenjang.
- Tanda tangan elektronik/integrasi penyedia tanda tangan.
- Team collaboration lebih lengkap.
- Billing dan paket langganan.
- Payment gateway Indonesia.
- Usage quota.
- API dan webhooks.
- Custom domain.
- Marketplace template.
- AI Form Generator.
- Analytics lanjutan.

Jangan membangun fitur-fitur ini sebagai bagian dari tahap MVP kecuali pengguna mengubah prioritas.

---

## 17. UI/UX guidelines

Codex harus membuat UI yang terasa seperti SaaS profesional, bukan halaman admin generik.

### Prinsip

- Hierarki visual jelas.
- Spacing konsisten.
- Tipografi mudah dibaca.
- Tidak terlalu banyak warna.
- Tombol utama jelas.
- Empty state menjelaskan tindakan berikutnya.
- Form builder mengutamakan area kerja.
- Mobile public form harus sangat nyaman.
- Hindari animasi berat dan dependency UI besar.
- Gunakan komponen reusable.

### Komponen dasar

- AppShell.
- Sidebar.
- Topbar.
- WorkspaceSwitcher.
- PageHeader.
- Button.
- Input.
- Select.
- Modal/Drawer.
- Dropdown.
- Badge.
- DataTable.
- EmptyState.
- LoadingState.
- Toast/notification.
- FormField.
- ConfirmDialog.

Jangan membuat seluruh komponen sekaligus jika belum dibutuhkan. Bangun saat dipakai dan konsistenkan API-nya.

---

## 18. Testing strategy

Gunakan PHPUnit atau Pest sesuai pilihan starter project; jangan memasang keduanya tanpa alasan.

Jenis test:

- Unit: schema validation, field registry, formatting.
- Feature: authentication, workspace, form CRUD, publish, submission.
- Authorization: cross-workspace access dan role.
- Database: FK, unique constraint, version relation.
- Browser/E2E: hanya jika setup ringan dan benar-benar diperlukan.

Setiap bug yang diperbaiki harus ditambahkan regression test bila memungkinkan.

Sebelum tahap dinyatakan selesai:

```bash
php artisan test
npm run build
```

Tambahkan formatter/static analysis hanya jika dependency sudah disepakati dan kompatibel.

---

## 19. Perintah operasional development lokal

Perintah umum:

```bash
# Masuk project
cd ~/projects/formora

# Lihat status Git
git status --short

# Install dependency PHP
composer install

# Install dependency frontend
npm install

# Jalankan migration development
php artisan migrate

# Jalankan server Laravel
php artisan serve

# Jalankan Vite di terminal terpisah
npm run dev

# Jalankan test
php artisan test

# Build asset production
npm run build

# Lihat route
php artisan route:list

# Lihat status migration
php artisan migrate:status
```

Untuk development, jalankan `php artisan serve` dan `npm run dev` di dua terminal. Jika pengguna memakai Laravel Herd/Valet/Nginx lokal, ikuti lingkungan yang sudah tersedia dan jangan menginstal server tambahan tanpa kebutuhan.

---

## 20. Definisi selesai proyek MVP

MVP baru boleh disebut selesai jika:

- Semua acceptance criteria tahap 0–11 yang relevan terpenuhi.
- Tidak ada test kritis yang gagal.
- Tidak ada akses lintas workspace.
- Public form hanya merender versi published.
- Submission terikat pada versi form yang tepat.
- Tidak ada data sensitif mentah di log.
- Builder, renderer, dan submission berjalan end-to-end.
- Export berjalan dengan otorisasi.
- UI responsive.
- Dokumentasi setup lokal lengkap.
- Ada prosedur backup dan restore yang sudah diuji.
- Ada daftar risiko yang diketahui.

Jangan menyatakan production-ready hanya karena aplikasi bisa dibuka di browser.

---

## 21. Prompt pertama untuk Codex

Setelah file ini disimpan sebagai `docs/IMPLEMENTATION_PLAN.md`, berikan prompt berikut kepada Codex:

```text
Mulai proyek Formora.

Baca docs/IMPLEMENTATION_PLAN.md seluruhnya dan ikuti AGENTS.md jika sudah ada.
Saat ini kerjakan TAHAP 0 saja: audit lingkungan development lokal.

Periksa OS, PHP, Composer, PostgreSQL, Node, npm, Git, dan direktori project.
Jangan instal paket, jangan mengubah konfigurasi, jangan membuat project,
dan jangan menghapus atau menimpa file apa pun pada tahap ini.

Jika hasil audit selesai:
1. Buat AGENTS.md sesuai template dalam implementation plan.
2. Buat docs/PROGRESS.md dan catat hasil audit serta tahap aktif.
3. Laporkan kebutuhan yang belum tersedia dan rekomendasi langkah berikutnya.
4. Berhenti. Jangan mulai Tahap 1 sebelum saya menyetujuinya.

Gunakan output ringkas. Jangan mencetak isi direktori besar atau file dependency.
```

### Prompt untuk melanjutkan tahap berikutnya

```text
Lanjutkan hanya ke tahap berikutnya yang belum selesai menurut docs/PROGRESS.md.
Ikuti seluruh aturan AGENTS.md dan docs/IMPLEMENTATION_PLAN.md.
Periksa git status, kerjakan tahap itu saja, jalankan validasi, update progress,
laporkan hasil ringkas, lalu berhenti menunggu persetujuan.
```

---

## 22. Catatan keputusan yang belum boleh diasumsikan Codex

Jika belum ditentukan oleh pengguna, Codex harus mencatat dan bertanya saat keputusan itu diperlukan:

- Nama domain final.
- Logo dan identitas visual final.
- Apakah workspace personal otomatis dibuat saat registrasi (rekomendasi: ya).
- Batas jumlah workspace/form/submission per paket.
- Provider object storage.
- Provider email transaksional.
- Payment gateway.
- Kebijakan retensi data dan masa penyimpanan file.
- Detail role/permission untuk organisasi besar.
- Sumber resmi data referensi bank dan wilayah.
- Ketentuan legal setiap template dokumen.

**Jangan menunda fondasi teknis karena keputusan komersial yang belum diperlukan.** Catat sebagai pending decision dan lanjutkan hanya pada bagian yang tidak bergantung padanya.
