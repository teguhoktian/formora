# Formora Development Progress

## Tahap aktif

Tahap 11 — Hardening MVP selesai; menunggu persetujuan sebelum pekerjaan
backlog pasca-MVP. Backup/restore rehearsal pada target disposable deployment
tetap menjadi langkah operasional sebelum peluncuran.

Penyempurnaan yang diminta setelah Tahap 11: scrollbar sidebar dibuat konsisten
lebarnya dan hanya menampilkan thumb saat hover/fokus; Smart Field nama bank
menggunakan daftar bank umum OJK Juni 2026 dan validasi server. Tahap lanjutan
pasca-MVP belum didefinisikan dalam rencana dan tetap dikerjakan berurutan setelah
ruang lingkup tahap berikutnya disepakati.

Validasi penyempurnaan:
- `php artisan test --compact tests/Feature/SmartFieldTest.php` — lulus, 5 test / 58 assertion.
- `vendor/bin/pint --test app/Services/SmartFieldRegistry.php app/Services/PublicSubmissionService.php tests/Feature/SmartFieldTest.php` — lulus.
- `composer run types:check --no-interaction` — lulus, PHPStan 0 error.
- `npm run build` — lulus; ada peringatan opsional `fontaine` untuk fallback font.
- `git diff --check` — lulus.

## Hasil Tahap 0

- Waktu audit: 18 November 2026 (Asia/Bangkok).
- OS: Ubuntu 24.04.4 LTS pada WSL2, kernel Linux 6.6.87.2-microsoft-standard-WSL2, x86_64.
- PHP CLI: 8.3.6.
- Composer: 2.7.1.
- PostgreSQL client (`psql`): 16.15.
- PostgreSQL server: cluster 16/main online pada port 5432; `pg_isready` menerima koneksi lokal.
- Node.js: v24.14.0.
- npm: 11.9.0.
- Git: 2.43.0.
- Lokasi proyek: `/home/oktian/projects/formora`; direktori sudah ada dan sebelum audit hanya berisi `docs/IMPLEMENTATION_PLAN.md`.
- Direktori ini belum merupakan repository Git. Perintah status, branch, dan log tidak dapat dijalankan karena metadata `.git` tidak ada.
- Tidak ada paket yang dipasang dan tidak ada konfigurasi sistem yang diubah.

## Kebutuhan / tindak lanjut

- Tidak ada CLI atau layanan PostgreSQL lokal yang hilang berdasarkan audit Tahap 0.
- Inisialisasi repository Git belum dilakukan; tentukan/lakukan saat diperlukan pada tahap berikutnya.
- Versi Node/npm tercatat, tetapi kompatibilitasnya dengan toolchain frontend Laravel perlu diperiksa saat bootstrap.

## Tahap 1 — Bootstrap Laravel

Status: selesai.

- Laravel 13.43.0 dan Livewire 4.4.8 dengan starter kit Livewire resmi.
- PHP 8.3 kompatibel; frontend memakai Vite 8.3.8 standar dan Tailwind CSS 4.3.5.
- Autentikasi starter tersedia; Alpine dimuat oleh Livewire, tidak dimasukkan lagi ke `resources/js/app.js`.
- Halaman awal Formora berbahasa Indonesia dibuat di `resources/views/welcome.blade.php`.
- `.env.example` menggunakan PostgreSQL dengan nama DB/user `formora` dan password kosong sebagai placeholder. `.env` lokal memakai SQLite untuk menjalankan bootstrap tanpa mengubah PostgreSQL sistem.
- Panduan setup lokal: `docs/LOCAL_SETUP.md`.
- Sail dihapus; tidak ada konfigurasi Docker yang dibuat.

### Validasi Tahap 1

- `composer install --no-interaction` — lulus.
- `npm install` — lulus.
- `php artisan test` — lulus, 38 test / 99 assertion.
- `npm run build` — lulus dengan Vite 8.3.8.
- `composer run lint:check` — lulus (Pint).
- `composer run types:check` — lulus (PHPStan, 0 error).
- `php artisan serve --host=127.0.0.1` + HTTP GET `http://127.0.0.1:8000/` — HTTP 200; teks Formora dan asset tampil.

### Risiko / kebutuhan lokal

- PostgreSQL server online, tetapi role `oktian` tidak tersedia untuk autentikasi peer dan hak sudo tanpa password tidak tersedia. Database/user `formora` belum dibuat; isi kredensial `.env` dan siapkan database sesuai `docs/LOCAL_SETUP.md` sebelum memakai PostgreSQL.
- `npm audit` melaporkan 6 advisori high yang berasal dari satu advisori `picomatch` transitif (GHSA-fc5q-39xc-vjpf). Perbaikan otomatis npm akan menurunkan Vite ke versi mayor 6 yang tidak kompatibel dengan plugin Vite saat ini; advisori yang dicek belum mencantumkan versi patch. Jangan jalankan `npm audit fix --force`; tinjau kembali setelah upstream merilis perbaikan.
- Percobaan `php artisan test` yang berlangsung bersamaan dengan build aset sempat membaca manifest saat build mengganti file. Pengujian berurutan setelah build selesai lulus semua.
- Pada akhir Tahap 1 belum ada metadata Git; repository kini tersedia pada branch `main`.

## Tahap 2 — Fondasi database dan model

Status: selesai.

- Menambah tabel `workspaces`, `workspace_members`, `forms`, `form_versions`, `form_submissions`, dan `audit_logs`. Migration `users` bawaan tidak diubah.
- UUID dipakai untuk workspace, form, versi, dan submission. Schema form serta jawaban submission memakai JSONB di PostgreSQL.
- Foreign key komposit mencegah submission memakai workspace/form yang tidak cocok atau versi milik form lain.
- Enum database membatasi role dan status. Foreign key, indeks, aturan delete, dan unique constraint dicatat di `docs/DATABASE.md`.
- Menambahkan Eloquent models, relationships, dan factories. Seeder tidak lagi membuat user contoh.
- PostgreSQL `formora` sudah berisi tabel bawaan serta satu user sebelum Tahap 2. Migration hanya menambah tabel; user dan record bawaan tetap ada. Semua tabel produk baru masih kosong.

### Validasi Tahap 2

- `php artisan migrate --force` pada PostgreSQL `formora` — keenam migration lulus.
- `php artisan migrate:status` — seluruh migration berstatus Ran.
- `php artisan db:show --counts` — enam tabel produk baru memiliki 0 row; tabel `users` tetap memiliki 1 row.
- Database scratch SQLite kosong: seluruh migration naik, lalu `migrate:rollback --step=6` berhasil; database scratch dihapus setelah validasi.
- Constraint PostgreSQL diuji dalam transaksi yang di-rollback: insert valid/JSONB berhasil; versi form yang salah, referensi lintas workspace, dan status invalid ditolak.
- Eloquent UUID, JSONB cast, dan relasi versi diuji di PostgreSQL dalam transaksi yang di-rollback.
- `composer test --no-interaction` — lulus; Pint dan PHPStan lulus, 48 test / 120 assertion lulus.
- `npm run build` — lulus.
- `git diff --check` — lulus.

### Risiko / backlog Tahap 2

- Proses publish dan immutable version diselesaikan pada Tahap 6.
- Policy, isolasi workspace pada operasi aplikasi, serta aturan lifecycle anggota dikerjakan pada Tahap 3; constraint database saat ini sudah melindungi pasangan workspace/form submission.
- Retention, penghapusan terkontrol, dan review kebijakan audit tetap menjadi pekerjaan hardening.

## Tahap 3 — Autentikasi dan workspace

Status: selesai.

- Alur register/login/logout/reset password dan profil tetap menggunakan starter kit Fortify/Livewire.
- Registrasi membuat user, workspace personal, dan membership owner dalam satu transaksi.
- Pengguna terautentikasi dapat membuat workspace tambahan; pemilihan workspace hanya menerima workspace yang memiliki membership.
- Middleware web menyelesaikan workspace aktif dari session dan memeriksa ulang membership pada setiap request.
- Menambahkan endpoint pengelolaan anggota: tambah anggota yang sudah terdaftar, ubah role admin/member/viewer, hapus anggota, dan transfer ownership. Owner hanya dapat dihapus setelah transfer; role owner tidak dapat ditetapkan lewat endpoint role biasa.
- Policies membatasi akses workspace, form, dan anggota menurut membership serta role. Viewer hanya dapat melihat form; member tidak dapat mengelola anggota; admin/owner dapat mengelola anggota.
- Tidak ada perubahan schema atau migrasi database pada tahap ini; data PostgreSQL yang sudah ada tidak diubah.

### Validasi Tahap 3

- `composer test --no-interaction` — lulus; Pint dan PHPStan lulus, 54 test / 138 assertion lulus.
- Tes keamanan mencakup isolasi workspace/form, penolakan workspace ID milik user lain, batas viewer/member, serta transfer ownership sebelum menghapus owner.
- `npm run build` — lulus; Vite mencatat bahwa fallback font opsional membutuhkan paket `fontaine`, sama seperti build sebelumnya.
- `git diff --check` — lulus.

## Tahap 4 — Dashboard custom

Status: selesai.

- Mengganti dashboard placeholder dengan overview Formora berbahasa Indonesia dalam layout sidebar responsif yang konsisten dengan halaman pengaturan.
- Menampilkan workspace aktif, jumlah form, jumlah form terpublikasi, total respons, form terbaru, respons terbaru, dan aktivitas terakhir dari database workspace aktif.
- Pengalih workspace berfungsi di sidebar; form pembuatan workspace tersedia di dashboard. Form dan respons lain tidak ikut terbaca lintas workspace.
- Empty state, indikator pembaruan Livewire berkala, pesan validasi workspace, label aksesibilitas, dan menu responsif ditambahkan.
- Navigasi menuju ringkasan, form, respons, workspace, dan pengaturan berfungsi. Template, Smart Fields, dan analitik ditandai “Segera hadir”; CTA pembuatan form dinonaktifkan sampai Form Builder tersedia.
- Setelah Form Builder tersedia pada Tahap 5, CTA pembuatan form diarahkan ke daftar form dan hanya tampil bagi role yang boleh membuat form.

### Validasi Tahap 4

- `composer test --no-interaction` — lulus; Pint dan PHPStan lulus, 56 test / 145 assertion lulus.
- Tes dashboard membuktikan user tanpa membership tidak melihat form workspace lain dan anggota hanya melihat data workspace aktif.
- `npm run build` — lulus; Vite mencatat bahwa fallback font opsional membutuhkan paket `fontaine`.
- `git diff --check` — lulus.

## Tahap 5 — Form Builder: fondasi schema

Status: selesai.

- Menambahkan daftar dan pembuatan form dalam workspace aktif, serta halaman edit yang hanya dapat dibuka anggota workspace dengan izin update. Workspace ID dari request tidak dipercaya.
- Membuat draft schema JSON berversi dengan UUID stabil untuk halaman dan field. Validasi backend membatasi tipe field ke text, textarea, email, phone, number, date, select, radio, checkbox, dan file; nama field unik tanpa membedakan kapitalisasi.
- Menambahkan builder Livewire dengan tab Build, Design, Logic, Settings, dan Preview; toolbox, canvas, inspector, tambah/hapus/duplikasi field, tambah/hapus halaman, edit pengaturan, dan autosave draft.
- Reorder menggunakan SortableJS dan mengirim urutan UUID sekali setelah drop; server memverifikasi semua ID cocok dengan field pada halaman sebelum menyimpan.
- Draft baru dibuat dari schema default. Jika form hanya memiliki versi published, builder membuat draft baru dari schema tersebut dan tidak mengubah versi published.
- Tidak ada migrasi database pada tahap ini; struktur JSONB yang ada digunakan. Publishing dan versi immutable diselesaikan pada Tahap 6.

### Validasi Tahap 5

- `vendor/bin/pint --parallel` — lulus.
- `composer test --no-interaction` — lulus; PHPStan 0 error, 69 test / 174 assertion lulus. Cakupan baru menguji validasi schema, autosave/reload, reorder UUID, duplikasi/halaman, role, dan isolasi workspace.
- `npm run build` — lulus dengan Vite; fallback font opsional memberi peringatan `fontaine`.
- `git diff --check` — lulus.

## Tahap 6 — Versioning dan publish

Status: selesai.

- Menambahkan pointer `published_version_id` pada form; constraint komposit memastikan versi aktif milik form yang sama.
- Publish berjalan dalam transaksi dengan row lock pada form, memvalidasi schema, menerbitkan draft sebagai snapshot immutable, dan memindahkan pointer versi aktif. Permintaan membawa ID draft yang diharapkan agar double-submit/stale request tidak menerbitkan draft berikutnya; setelah publish, builder menyediakan draft baru dari snapshot tersebut.
- Versi published tidak dapat diedit atau dihapus; model guard dan database trigger menolak perubahan. Pause mempertahankan pointer; resume mengaktifkannya kembali; unpublish mengosongkan pointer tanpa menghapus riwayat versi.
- Hanya owner dan admin workspace yang dapat publish, jeda, lanjutkan, atau menghentikan publikasi. Member tetap dapat menyunting draft.
- Submission historis tetap terhubung ke versi yang dijawab saat versi publik berganti.
- Menambah migration aditif untuk pointer versi aktif dan memperbarui `docs/DATABASE.md`. Tidak ada migrasi yang dijalankan terhadap database lokal berisi data.

### Validasi Tahap 6

- `vendor/bin/pint --parallel` — lulus.
- `composer test --no-interaction` — lulus; Pint lulus, PHPStan 0 error, 76 test / 202 assertion lulus.
- Tes mencakup pointer dan constraint lintas form, publish permission, immutable version termasuk query builder, submission historis, pause/resume/unpublish, dan permintaan publish stale yang beradu.
- Migration `2026_11_18_000007_add_published_version_to_forms_table` diterapkan ke PostgreSQL lokal `formora`; `migrate:status` menunjukkan Ran dan `db:table forms` menampilkan `published_version_id` beserta FK komposit. Smoke test publish PostgreSQL dalam transaksi lalu di-rollback — lulus.
- `git diff --check` — lulus.

## Tahap 7 — Public form renderer dan submission

Status: selesai.

- Menambahkan URL publik unik per form. Slug dibuat saat publish dan migration mengisi slug untuk form yang sudah ada.
- Form publik hanya membaca versi published aktif; draft, paused, unpublished, dan submission detail tidak tersedia melalui endpoint publik.
- Renderer menampilkan halaman form bertahap dan semua tipe field yang didukung. Server memvalidasi required, format, pilihan, nilai field yang diizinkan, MIME/ukuran file individual (maksimal 10 MB), serta total upload maksimal 25 MB.
- Submission menyimpan workspace/form/versi dari database, status `new`, nomor referensi unik, dan jawaban JSONB. Nomor referensi hanya muncul pada sesi konfirmasi; isi jawaban tidak di-flash kembali ke session ketika validasi gagal.
- File disimpan di disk privat tanpa URL publik. Honeypot dan rate limit 10 pengiriman per menit diterapkan.
- Migration `2026_11_18_000008_add_public_slug_and_submission_reference` diterapkan ke PostgreSQL lokal; slug dan reference submission lama diisi dari ID, tanpa mengubah jawaban. Smoke test publikasi dan submission PostgreSQL dalam transaksi lalu di-rollback — lulus.

### Validasi Tahap 7

- `vendor/bin/pint --parallel` — lulus.
- `composer test --no-interaction` — lulus; Pint lulus, PHPStan 0 error, 84 test / 258 assertion lulus.
- Tes mencakup render tanpa login, hanya schema published, submission ke versi tepat, validasi field, isolasi workspace, file privat, honeypot, rate limit, halaman bertahap, dan ketiadaan endpoint baca submission publik.
- `npm run build` — lulus dengan peringatan fallback font opsional `fontaine`.
- `git diff --check` — lulus.

## Backlog / keputusan tertunda

- Domain final, identitas visual, batas paket, provider object storage/email/pembayaran, kebijakan retensi, role organisasi, sumber data referensi, dan ketentuan legal template dicatat di `docs/IMPLEMENTATION_PLAN.md` dan ditanyakan saat relevan.

## Tahap 8 — Submission management

Status: selesai.

- Menambahkan daftar respons per form dengan pagination 25 item, pencarian jawaban/referensi, filter status dan tanggal, serta detail respons. Query daftar dibatasi ke kolom yang ditampilkan dan seluruh akses dibatasi workspace aktif.
- Detail dan CSV memasking field yang terindikasi sensitif (termasuk NIK, NPWP, rekening, token, kata sandi) dan tidak menampilkan path file privat. Nilai input yang dapat menjadi formula spreadsheet dinetralkan pada CSV.
- Menambahkan status tindak lanjut dan catatan internal. Owner/admin/member dapat memperbarui status; viewer hanya dapat melihat. Ekspor dan unduh hanya untuk owner/admin.
- Ekspor CSV memakai queue job, membaca submission per batch 200 baris, menggunakan filter daftar yang sama, menyimpan file pada disk privat, dan menyediakan unduhan setelah selesai. Header berisi kolom metadata dan nama field; tiap submission menjadi satu baris. Audit hanya mencatat event dan ID form/submission tanpa isi jawaban atau catatan.
- Migration `2026_11_18_000009_add_internal_notes_to_form_submissions` dan `2026_11_18_000010_create_submission_exports_table` diterapkan ke PostgreSQL lokal; keduanya berstatus Ran.

### Validasi Tahap 8

- `php artisan test --compact tests/Feature/SubmissionManagementTest.php` — lulus, 5 test / 29 assertion.
- `php artisan test --compact` — lulus, 89 test / 287 assertion. Pint dan PHPStan juga lulus setelah perubahan format CSV.
- Migration PostgreSQL dan `php artisan migrate:status` — kedua migration Tahap 8 berstatus Ran.
- `git diff --check` — lulus.

**Berhenti setelah tahap 8.**

## Tahap 9 — Smart Fields Indonesia

Status: selesai; menunggu persetujuan pengguna sebelum Tahap 10.

- Menambahkan registry tunggal untuk 12 field: NIK, NPWP, tanggal lahir, rekening, nama bank, Rupiah, telepon Indonesia, alamat bertingkat, NIP, NISN, NUPTK, dan NIM. Registry menjadi sumber ID, label, toolbox control, input renderer, rules, normalisasi, formatting, dan klasifikasi masking.
- Smart fields tersedia di builder serta renderer publik. Input NIK, NPWP, rekening, NIP, NISN, dan NUPTK langsung menyaring karakter nonangka; backend tetap memvalidasi format dari registry, menyimpan nomor sebagai string untuk mempertahankan nol awal, menormalisasi separator NPWP/rekening/telepon, dan menampilkan pesan validasi berbahasa Indonesia pada field yang salah.
- NIK, NPWP, rekening, tanggal lahir, telepon, NIP, NISN, NUPTK, dan NIM dimasking pada detail serta CSV. Nominal Rupiah diformat; alamat ditampilkan menurut tingkat wilayah. Tidak ada lookup atau klaim verifikasi ke instansi.
- Alamat menyediakan pilihan berjenjang provinsi → kabupaten/kota → kecamatan → desa/kelurahan. Keempat tingkat memakai kode snapshot wilayah lokal dan server menolak pilihan yang tidak cocok dengan induknya. Sumber distribusi, tanggal data, batas format, dan caveat dicatat di `docs/SMART_FIELDS.md`.
- Nama bank tetap berupa teks manual. Perbaikan ini melengkapi pengalaman Smart Fields Tahap 9 dan tidak memulai Tahap 10.

### Validasi Tahap 9

- `composer test --no-interaction` — lulus; Pint lulus, PHPStan 0 error, 94 test / 342 assertion.
- `npm run build` — lulus dengan peringatan opsional font fallback `fontaine`.
- `git diff --check` — lulus.

Validasi tambahan memastikan pesan NIK tampil pada halaman publik dalam bahasa
Indonesia, pesan NIP tidak membocorkan key translasi, dan label alamat menampilkan
nama wilayah dari kode yang disimpan.

**Berhenti setelah tahap 9.**

## Tahap berikutnya

## Tahap 10 — Template Library

Status: selesai; menunggu persetujuan pengguna sebelum Tahap 11.

- Menyediakan kategori, pencarian judul/deskripsi, preview schema, tiga template resmi awal (pendaftaran kegiatan, survei kepuasan, permintaan informasi), dan kartu template komunitas yang telah disetujui.
- Anggota yang boleh mengedit form dapat menyimpan schema sebagai template workspace privat. Template dapat diatur private, unlisted, atau public; pengajuan public masuk antrean moderasi dan baru tampil setelah moderator platform menyetujui.
- Menambahkan salinan form dari template dengan ID halaman/field baru agar schema salinan dapat diedit tanpa mengubah sumber. Versi template mengambil snapshot dari form workspace; snapshot lama tidak dapat diedit/dihapus melalui model.
- Akses template dibatasi berdasarkan visibilitas dan workspace. Role viewer tidak dapat menyimpan atau memakai template untuk membuat form. Akses moderator platform dapat diberikan/dicabut operator server lewat `php artisan formora:templates:moderator EMAIL [--revoke]`.
- Migration `2026_11_18_000011_create_form_templates_tables` diterapkan ke PostgreSQL lokal. Tiga template resmi dimasukkan melalui migration; tidak ada data user/submission contoh yang dibuat.

### Validasi Tahap 10

- `php artisan test --compact tests/Feature/TemplateLibraryTest.php` — lulus, 7 test / 50 assertion.
- `composer test --no-interaction` — lulus; Pint lulus, PHPStan 0 error, 101 test / 392 assertion.
- `npm run build` — lulus dengan peringatan opsional font fallback `fontaine`.
- `php artisan migrate --force --no-ansi` dan `php artisan migrate:status --no-ansi` — migration Tahap 10 berstatus Ran pada PostgreSQL lokal.
- Smoke test PostgreSQL — tiga template resmi terbaca dan seluruh snapshot schema lolos `FormSchemaService`.
- `git diff --check` — lulus.

**Berhenti setelah tahap 10.**

## Tahap berikutnya

## Tahap 11 — Hardening MVP

Status: selesai untuk cakupan aplikasi dan dokumentasi; menunggu persetujuan
sebelum pekerjaan backlog pasca-MVP.

- Security review: request web tetap melewati CSRF Laravel; konfigurasi default
  dan `.env.example` mengenkripsi sesi, cookie HttpOnly/SameSite Lax, cookie
  Secure aktif untuk production, file disk lokal privat tidak dapat dilayani
  sebagai temporary URL, dan response web menambah header keamanan dasar. HSTS
  hanya dikirim pada HTTPS.
- Mempertahankan isolasi workspace dan menambah tes matriks authorization
  owner/admin/member/viewer untuk edit, lihat respons, publish, dan ekspor.
- Pengiriman publik memiliki limit 10/menit; permintaan ekspor dibatasi 5/menit.
  Validasi MIME/ukuran upload, honeypot, dan pengujian isolasi sebelumnya tetap
  berlaku.
- Menambah halaman pemberitahuan privasi awal dan tautan dari beranda/form publik.
  Dokumen menjelaskan pemrosesan, akses, dan status retensi; tidak mengklaim
  kebijakan legal final. Persetujuan per form dan kontak operator perlu
  ditentukan sebelum layanan publik.
- Setup lokal memuat arahan deployment aman, rotasi log harian 14 hari, backup
  PostgreSQL yang harus dienkripsi/dibatasi aksesnya oleh operator, prosedur
  pemulihan ke database terpisah, dan desain penghapusan. Tidak ada penghapusan
  otomatis submission atau file karena masa retensi belum ditentukan.
- Pemeriksaan pagination/query: daftar submission tetap server-side 25 baris;
  export tetap batch 200. Pencarian jawaban JSONB memakai scan substring dan
  dapat perlu indeks/perubahan desain saat volume data meningkat.
- Rehearsal restore sengaja tidak dijalankan pada database lokal yang mungkin
  berisi data pengguna; lakukan pada target disposable milik deployment sebelum
  peluncuran. Profiling beban dan pemeriksaan visual perangkat nyata juga
  menjadi validasi lingkungan deployment.

### Validasi Tahap 11

- `php artisan test --compact tests/Feature/HardeningTest.php` — lulus, 4 test /
  37 assertion.
- `composer test --no-interaction` — lulus; Pint, PHPStan 0 error, 105 test /
  429 assertion.
- `npm run build` — lulus; Vite menampilkan peringatan opsional paket
  `fontaine` untuk optimasi font.
- `git diff --check` — lulus.

**Berhenti setelah tahap 11.**

## Penyempurnaan fitur pasca-MVP — batch Smart Fields dan Analitik

Status: batch ini selesai; menunggu persetujuan sebelum batch fitur berikutnya.

- Menambahkan halaman katalog Smart Fields dari registry yang sudah ada. Halaman menampilkan field yang didukung, metadata, penanda data sensitif, dan keterangan bahwa pemeriksaan format bukan verifikasi identitas resmi.
- Menambahkan halaman Analitik berbasis agregat workspace aktif: jumlah form, form terbit, submission total dan 30 hari terakhir, ringkasan status, tren enam bulan, serta form dengan respons terbanyak. Query tidak mengambil isi jawaban submission.
- Mengganti stub sidebar Smart Fields dan Analitik dengan tautan aktif.
- Pada saat batch ini dicatat, pilihan pengguna untuk fitur Logika builder adalah menampilkan/menyembunyikan field secara kondisional; implementasi dicatat pada batch lanjutan di bawah.

### Validasi batch

- `php artisan test --compact tests/Feature/WorkspaceFeaturePagesTest.php` — lulus, 3 test / 16 assertion.
- `composer run types:check --no-interaction` — lulus, PHPStan 0 error.
- `npm run build` — lulus; ada peringatan opsional `fontaine` untuk fallback font.
- `vendor/bin/pint --test app/Http/Controllers/AnalyticsController.php app/Http/Controllers/SmartFieldsController.php routes/web.php tests/Feature/WorkspaceFeaturePagesTest.php` — lulus.
- `git diff --check` — lulus.
- `composer test --no-interaction` — belum lulus: 104 dari 108 test lulus; 4 gagal. Kegagalan yang terlihat berada pada assertion dashboard dan pengaturan keamanan; area tersebut tidak disentuh batch ini. Perlu triase tersendiri sebelum menjadikan suite penuh sebagai gerbang.

### Backlog fitur berikutnya

- Tab Design builder masih belum berfungsi.
- Pencarian pada header starter merupakan view/layout yang tidak dipakai oleh halaman aktif; belum diputuskan apakah akan diaktifkan sebagai pencarian produk.

## Penyempurnaan fitur pasca-MVP — halaman Respons, Workspace, dan Desain builder

Status: implementasi dan validasi selesai.

- Menu Respons kini membuka halaman workspace dengan filter status dan pagination. Daftar hanya menampilkan metadata respons dan judul form; isi jawaban tidak diambil.
- Menu Workspace kini membuka halaman khusus untuk melihat workspace aktif, berpindah workspace, membuat workspace, serta mengelola anggota/role dan transfer ownership melalui endpoint yang sudah memiliki otorisasi.
- Tab Desain builder diaktifkan dengan warna aksen (hijau/biru/ungu), lebar konten (ringkas/standar/lebar), preview tampilan, autosave, dan penerapan tema pada renderer publik.
- Pada batch ini tab Logika masih belum tersedia. IMPLEMENTATION_PLAN.md hanya mencantumkan nama tab Logic pada Tahap 5 dan belum menetapkan perilaku maupun acceptance criteria. Pilihan pengguna untuk kondisi tampil/sembunyi field dicatat sebagai backlog saat itu.

- `php artisan test --compact tests/Feature/WorkspaceNavigationAndDesignTest.php` — lulus, 3 test / 9 assertion.
- `php artisan test --compact tests/Feature/FormBuilderTest.php tests/Feature/PublicFormTest.php tests/Feature/SubmissionManagementTest.php tests/Feature/WorkspaceNavigationAndDesignTest.php` — lulus, 24 test / 113 assertion.
- `vendor/bin/pint --test app/Http/Controllers/SubmissionController.php app/Http/Controllers/WorkspaceController.php app/Livewire/FormBuilder.php app/Services/FormSchemaService.php routes/web.php tests/Feature/WorkspaceNavigationAndDesignTest.php` — lulus.
- `composer run types:check --no-interaction` — lulus, PHPStan 0 error.
- `npm run build` — lulus; ada peringatan opsional `fontaine` untuk fallback font.
- `php artisan route:list --path=responses` dan `php artisan route:list --path=workspace` — route index tersedia.
- `git diff --check` — lulus.
- Tidak ada migration atau perubahan database.

### Perbaikan — pilihan pemicu Logika saat berpindah field

- Elemen dropdown editor Logika sebelumnya memakai identitas DOM tetap. Saat target diganti, Livewire dapat mempertahankan pilihan DOM sebelumnya walaupun state server sudah memuat aturan target baru. Tambahkan `wire:key` yang mengikuti target dan field pemicu agar kontrol dimuat ulang sesuai aturan tiap field.
- Menambahkan regression test bahwa dua target memuat field pemicu dan nilai kondisi masing-masing.
- `php artisan test --compact tests/Feature/FormBuilderTest.php` — lulus, 10 test / 25 assertion.
- `vendor/bin/pint --test app/Livewire/FormBuilder.php tests/Feature/FormBuilderTest.php` — lulus.
- `git diff --check` — lulus.

## Penyempurnaan pasca-MVP — validasi jawaban per field

Status: implementasi selesai.

- Builder menyimpan aturan validasi pada schema setiap field. Teks/paragraf mendukung panjang minimum/maksimum, frasa wajib/terlarang, dan pola aman sederhana; angka mendukung perbandingan atau rentang; tanggal mendukung rentang; checkbox mendukung jumlah pilihan; unggahan mendukung jenis, jumlah, dan ukuran file. Tipe lain tetap memakai validasi bawaan dan menerima pesan kesalahan khusus.
- Validasi backend menjadi sumber kebenaran. Pesan kesalahan dapat dikustomisasi. Pola teks dibatasi panjang/sintaks dan menolak pengelompokan serta wildcard kuantitas berisiko.
- File tetap privat; unggahan dapat berisi maksimal 5 file dengan ukuran maksimal 10 MB per file dan total 25 MB per submission. Respons dan ekspor hanya menyebut jumlah lampiran, tidak memaparkan path privat.
- Tidak ada migration atau perubahan database.

### Validasi batch

- `composer run types:check --no-interaction` — lulus, PHPStan 0 error.
- `vendor/bin/pint --parallel --test` — lulus di dalam `composer test`.
- `npm run build` — lulus; peringatan opsional `fontaine` untuk fallback font.
- `php artisan test --compact tests/Unit/FormSchemaServiceTest.php tests/Feature/FormBuilderTest.php tests/Feature/PublicFormTest.php` — lulus, 30 test / 126 assertion.
- `composer test --no-interaction` — 116 dari 120 test lulus. Empat kegagalan berada di `DashboardTest` dan `Settings/SecurityTest`, area yang tidak disentuh batch ini dan sebelumnya tercatat gagal pada progress. Tes yang terkait batch ini lulus.
- `git diff --check` — lulus.

## Penyempurnaan fitur pasca-MVP — Logika tampil/sembunyi field

Status: implementasi selesai untuk kondisi satu aturan per field pada halaman yang sama.

- Tab Logika builder dapat memilih field target dan mengatur agar field tersebut tampil ketika field Dropdown atau pilihan tunggal bernilai sama dengan opsi tertentu. Kondisi dapat dihapus; server memvalidasi field sumber, halaman, tipe, dan opsi.
- Schema form menyimpan aturan visibilitas pada definisi field, tanpa migration database.
- Renderer publik memperbarui visibilitas saat jawaban berubah. Kontrol tersembunyi dinonaktifkan dan nilainya dibersihkan; server juga melewati validasi required untuk field tersembunyi serta membuang jawaban tersembunyi yang dipaksakan melalui request.
- Batas cakupan: belum mendukung kondisi lintas halaman, operator selain sama dengan, beberapa kondisi/kelompok AND-OR, atau menyembunyikan field pemicu. Dropdown dan pilihan tunggal adalah tipe pemicu yang didukung.

### Validasi batch

- `php artisan test --compact tests/Unit/FormSchemaServiceTest.php tests/Feature/FormBuilderTest.php tests/Feature/PublicFormTest.php` — lulus, 24 test / 97 assertion.
- `composer run types:check --no-interaction` — lulus, PHPStan 0 error.
- `vendor/bin/pint --test app/Livewire/FormBuilder.php app/Services/FormSchemaService.php app/Services/PublicSubmissionService.php tests/Feature/FormBuilderTest.php tests/Feature/PublicFormTest.php tests/Unit/FormSchemaServiceTest.php` — lulus.
- `npm run build` — lulus; peringatan opsional `fontaine` untuk fallback font (dijalankan selama batch sebelum perbaikan PHP-only).
- `git diff --check` — lulus.
- Tidak ada migration atau perubahan database.
