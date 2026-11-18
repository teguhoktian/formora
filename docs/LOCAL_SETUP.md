# Menjalankan Formora secara lokal

Prasyarat: PHP 8.3 dengan ekstensi Laravel yang diperlukan, Composer, Node.js/npm,
dan PostgreSQL. Formora memakai Laravel 13, Livewire, Blade, Tailwind CSS, dan Vite.

1. Siapkan database dan akun PostgreSQL lokal bernama `formora`. Atur password
   lokal yang Anda pilih; jangan simpan password asli ke source control.
2. Salin konfigurasi dan pasang dependency:

   ```bash
   cp .env.example .env
   composer install
   npm install
   ```

3. Isi `DB_USERNAME` dan `DB_PASSWORD` di `.env` sesuai database lokal, lalu
   jalankan:

   ```bash
   php artisan key:generate
   php artisan migrate
   ```

4. Jalankan Laravel dan Vite di dua terminal:

   ```bash
   php artisan serve
   npm run dev
   ```

   Buka `http://localhost:8000`.

Untuk pemeriksaan tahap ini, test PHPUnit menggunakan database in-memory yang
ditentukan di `phpunit.xml`. Email development dicatat melalui mailer `log`;
belum ada provider email eksternal yang dikonfigurasi.

## Deployment dan operasi

- Untuk deployment, gunakan `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
  HTTPS, `SESSION_SECURE_COOKIE=true`, kunci `APP_KEY` yang unik, serta rahasia
  DB/mail dari secret manager lingkungan. Jangan menyalin `.env` lokal.
- Session dienkripsi, memakai cookie `HttpOnly` dan `SameSite=Lax`; cookie
  `Secure` aktif otomatis di `APP_ENV=production` dan harus dipastikan aktif
  saat TLS berakhir di reverse proxy. Pastikan konfigurasi trusted proxy sesuai
  dengan proxy yang benar-benar digunakan.
- Route web memakai middleware CSRF Laravel. Penyelesaian form publik dibatasi
  10 permintaan/menit dan permintaan ekspor 5/menit. Login dan two-factor
  memakai limiter Fortify.
- Header web menetapkan `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`, dan `Permissions-Policy`; HSTS hanya dikirim untuk request
  HTTPS. File submission dan ekspor berada di disk `local` privat; disk ini
  tidak melayani temporary URL.
- Log memakai channel harian dengan retensi lokal 14 hari. Atur rotasi/retensi
  tambahan di platform log operator bila log dikirim ke luar host. Jangan
  menambahkan isi jawaban atau kredensial ke log.
- Pemberitahuan di `/privacy` adalah draf untuk pengujian, bukan kebijakan final.
  Sebelum layanan publik, operator perlu mengisi kontak, dasar pemrosesan, tujuan,
  masa simpan, mekanisme permintaan subjek data, serta alur persetujuan yang
  sesuai untuk tiap form. Formora belum menghapus submission/file secara
  otomatis; tetapkan kebijakan dan proses penghapusan sebelum produksi.

### Backup dan uji pemulihan PostgreSQL

Lakukan pada host/akun operator yang memiliki `pg_dump`, `createdb`, dan
`pg_restore`. Backup mengandung akun, konfigurasi form, serta kemungkinan data
pribadi submission: batasi aksesnya, enkripsi saat disimpan/ditransfer, dan
terapkan retensi backup yang disetujui. Contoh rehearsal menggunakan database
restore terpisah; jangan arahkan `pg_restore` ke database sumber.

```bash
umask 077
pg_dump --format=custom --no-owner --file="/secure-backups/formora-$(date +%F-%H%M%S).dump" formora
createdb formora_restore_check
pg_restore --no-owner --exit-on-error --dbname=formora_restore_check /secure-backups/formora-YYYY-MM-DD-HHMMSS.dump
psql --dbname=formora_restore_check --command='SELECT count(*) FROM forms; SELECT count(*) FROM form_submissions;'
```

Bandingkan hasil dengan sumber, uji login/alur aplikasi pada lingkungan restore,
catat durasi dan hasilnya, lalu hapus database rehearsal dan arsip hanya melalui
prosedur operator yang sudah ditinjau. Jangan menjalankan pemulihan pertama kali
di produksi. Rehearsal untuk deployment harus memakai target disposable khusus;
belum dijalankan terhadap database lokal bersama selama tahap ini.
