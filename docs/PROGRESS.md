# Formora Development Progress

## Tahap aktif

Tahap 5 — Form Builder: fondasi schema selesai; menunggu persetujuan pengguna
sebelum Tahap 6.

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

- Proses publish dan immutable version akan diterapkan pada Tahap 6.
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

Status: selesai; menunggu persetujuan pengguna sebelum Tahap 6.

- Menambahkan daftar dan pembuatan form dalam workspace aktif, serta halaman edit yang hanya dapat dibuka anggota workspace dengan izin update. Workspace ID dari request tidak dipercaya.
- Membuat draft schema JSON berversi dengan UUID stabil untuk halaman dan field. Validasi backend membatasi tipe field ke text, textarea, email, phone, number, date, select, radio, checkbox, dan file; nama field unik tanpa membedakan kapitalisasi.
- Menambahkan builder Livewire dengan tab Build, Design, Logic, Settings, dan Preview; toolbox, canvas, inspector, tambah/hapus/duplikasi field, tambah/hapus halaman, edit pengaturan, dan autosave draft.
- Reorder menggunakan SortableJS dan mengirim urutan UUID sekali setelah drop; server memverifikasi semua ID cocok dengan field pada halaman sebelum menyimpan.
- Draft baru dibuat dari schema default. Jika form hanya memiliki versi published, builder membuat draft baru dari schema tersebut dan tidak mengubah versi published.
- Tidak ada migrasi database pada tahap ini; struktur JSONB yang ada digunakan. Publishing dan versi immutable tetap untuk Tahap 6.

### Validasi Tahap 5

- `vendor/bin/pint --parallel` — lulus.
- `composer test --no-interaction` — lulus; PHPStan 0 error, 69 test / 174 assertion lulus. Cakupan baru menguji validasi schema, autosave/reload, reorder UUID, duplikasi/halaman, role, dan isolasi workspace.
- `npm run build` — lulus dengan Vite; fallback font opsional memberi peringatan `fontaine`.
- `git diff --check` — lulus.

## Backlog / keputusan tertunda

- Domain final, identitas visual, batas paket, provider object storage/email/pembayaran, kebijakan retensi, role organisasi, sumber data referensi, dan ketentuan legal template dicatat di `docs/IMPLEMENTATION_PLAN.md` dan ditanyakan saat relevan.

## Tahap berikutnya

Tahap 6 — Publishing dan lifecycle immutable form. Belum dikerjakan; mulai hanya setelah disetujui pengguna.
