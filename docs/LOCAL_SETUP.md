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
