# Smart Fields Indonesia

Registry implementasi berada di `app/Services/SmartFieldRegistry.php`. Field
disimpan sebagai nilai schema `smart_*`, dirender oleh form publik, divalidasi
server, dan disajikan melalui formatter/masking yang sama. Format di bawah hanya
memeriksa pola masukan; Formora tidak memverifikasi identitas ke instansi.

| Field | Aturan format Formora | Penanganan |
|---|---|---|
| NIK | 16 angka | Dimasking pada detail/ekspor |
| NPWP | 15 atau 16 angka | Tanda titik, strip, dan spasi dihapus; dimasking |
| Tanggal lahir | Tanggal `YYYY-MM-DD`, tidak di masa depan | Dimasking |
| Nomor rekening | 6–30 angka setelah tanda pemisah dihapus | Batas input Formora, bukan aturan antarbank; dimasking |
| Nama bank | Salah satu nama bank umum dalam snapshot OJK | Pilihan dropdown dan divalidasi kembali di server |
| Nominal Rupiah | Angka non-negatif | Disajikan dengan pemisah ribuan Rupiah |
| Telepon Indonesia | Pola `^(?:\\+62|62|0)8[0-9]{7,12}$` setelah normalisasi | Spasi, kurung, titik, dan strip dinormalisasi; format tidak memverifikasi operator atau alokasi nomor |
| Alamat bertingkat | Pilih provinsi → kabupaten/kota → kecamatan → desa/kelurahan | Tiap pilihan mengikuti wilayah induknya; semua tingkat disimpan sebagai kode dan diperiksa ulang di server |
| NIP | 18 angka | Dimasking |
| NISN | 10 angka | Dimasking |
| NUPTK | 16 angka | Dimasking |
| NIM | Teks maksimal 50 karakter | Dimasking; format institusi berbeda-beda |

Nama bank menggunakan snapshot lokal daftar kantor pusat Bank Umum Konvensional
dan Bank Umum Syariah yang diterbitkan OJK pada Juni 2026. Snapshot mencakup
bank umum nasional, BPD, dan kantor cabang bank luar negeri; BPR/BPRS tidak
termasuk dalam daftar tersebut. Daftar ada di `SmartFieldRegistry::BANK_NAMES`
dan perlu ditinjau saat OJK menerbitkan pembaruan. Sumber: [Daftar Alamat Kantor
Pusat Bank Umum dan Syariah — OJK](https://ojk.go.id/id/kanal/perbankan/data-dan-statistik/Pages/Daftar-Alamat-Kantor-Pusat-Bank-Umum-Dan-Syariah.aspx).

Data wilayah provinsi, kabupaten/kota,
kecamatan, dan desa/kelurahan disimpan sebagai snapshot lokal di `resources/data/`;
form tidak menghubungi layanan eksternal saat diisi. Snapshot berisi 38 provinsi,
514 kabupaten/kota, 7.285 kecamatan, dan 83.762 desa/kelurahan. Perbarui dengan
`php artisan formora:regions:refresh`; perintah mengambil dump dari repositori
[cahyadsn/wilayah](https://github.com/cahyadsn/wilayah/blob/master/db/wilayah.sql),
memvalidasi struktur dan relasi induk, lalu menulis snapshot berkas bertingkat.
Dump menyatakan datanya mengikuti Kepmendagri No. 300.2.2-2138 Tahun 2025 dan
terakhir diperbarui 13 Februari 2026. Repositori sumber memakai lisensi MIT.
Tinjau perubahan nama/kode sebelum memakai hasil refresh di produksi.

## Rujukan format

Rujukan resmi diperiksa pada 18 November 2026:

- BKN menyebut masukan NIP 18 digit dan NIK 16 digit pada [layanan konsultasi Kanreg VII BKN](https://apps-palembang.bkn.go.id/registration).
- DJP menjelaskan penggunaan NPWP 16 digit, termasuk NIK sebagai NPWP bagi penduduk, pada [pengumuman pemadanan NPWP](https://pajak.go.id/index.php/id/pengumuman/pemberian-layanan-pemadanan-nomor-pokok-wajib-pajak-secara-elektronik). Formora juga menerima format 15 digit lama.
- Referensi data Kemendikbud menjelaskan format [NISN 10 digit dan NUPTK 16 digit](https://referensi.data.kemdikbud.go.id/residu/informasi).

Pola NIK/NIP/NISN/NUPTK, NPWP, telepon, dan rekening yang lolos validasi belum
membuktikan nomor tersebut diterbitkan, aktif, atau cocok dengan pemiliknya.
