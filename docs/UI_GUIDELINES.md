# Panduan UI Formora

Dokumen ini mencatat sistem visual aplikasi saat ini. Gunakan sebagai acuan
ketika menambah atau merapikan halaman agar pengalaman Formora konsisten.

## Arah visual

- SaaS profesional yang terasa tenang, jelas, dan membantu pekerjaan.
- Gunakan bahasa Indonesia untuk navigasi, aksi, status, error, dan bantuan.
- Gunakan warna aksen seperlunya; hindari setiap kartu memiliki warna berbeda.
- Gunakan ikon sederhana dan konsisten. Hindari emoji sebagai pengganti ikon UI.

## Token

| Peran | Nilai |
|---|---|
| Aksen/aksi utama | Emerald 700 (`#047857`), hover Emerald 800 |
| Teks utama | Slate 950 |
| Teks sekunder | Slate 500–600 |
| Latar halaman | `#f7f8fa` |
| Permukaan | Putih |
| Garis/batas | Slate 200 |
| Font | Instrument Sans melalui token `font-sans` |
| Radius kontrol | 8–12 px |
| Radius panel/kartu | 16 px |

Gunakan hierarki judul yang jelas, angka memakai tabular numerals, serta
container konten sekitar `max-w-7xl`. Jaga jarak antarkomponen dengan skala
Tailwind 4/6/8, dan gunakan bayangan tipis hanya untuk mengangkat permukaan.

## Pola halaman

- App shell memakai sidebar untuk navigasi dan workspace aktif. Navigasi yang
  belum memiliki halaman khusus harus ditandai jelas atau tidak ditampilkan.
- Halaman daftar memakai page header yang menjelaskan konteks dan aksi utama,
  area filter yang ringkas, serta baris/kartu yang tetap nyaman pada layar kecil.
- Dashboard menampilkan metrik dari data nyata, lalu aktivitas dan empty state
  yang memberi tindakan berikutnya.
- Builder mengutamakan ruang canvas; toolbox dan inspector berada di sisi pada
  layar lebar dan menumpuk secara alami pada layar kecil.
- Form publik membatasi lebar baca, memiliki label dan error dekat kontrol,
  serta menggunakan tombol aksi yang mudah dijangkau di ponsel.
- Gunakan komponen Blade reusable seperti `x-page-header` saat pola yang sama
  muncul di lebih dari satu halaman.

## Kontrol dan aksesibilitas

- Label harus terhubung dengan input; berikan fokus keyboard yang terlihat.
- Tombol utama memakai aksen emerald; aksi sekunder berupa outline atau teks.
- Status tidak dibedakan hanya dengan warna; sertakan label yang jelas.
- Tampilkan loading, success, validation error, dan empty state dengan bahasa
  yang spesifik dan tidak menyalahkan pengguna.
- Pastikan konten tidak melebar keluar layar pada lebar 320 px, dan target
  sentuh memiliki ruang yang cukup.
