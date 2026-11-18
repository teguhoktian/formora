<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pemberitahuan Privasi · Formora</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <main class="mx-auto max-w-3xl px-5 py-10 sm:px-8 sm:py-16">
            <a href="{{ route('home') }}" class="text-sm font-semibold text-indigo-700">← Formora</a>
            <article class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
                <h1 class="text-3xl font-bold tracking-tight">Pemberitahuan Privasi</h1>
                <p class="mt-3 text-sm text-slate-500">Informasi awal untuk pengguna uji Formora.</p>

                <div class="mt-8 space-y-6 text-sm leading-7 text-slate-700">
                    <section>
                        <h2 class="font-semibold text-slate-900">Informasi yang diproses</h2>
                        <p>Formora memproses informasi akun dan workspace, konfigurasi formulir, jawaban yang dikirim responden, serta file yang dilampirkan. Formora juga menyimpan data teknis sesi dan catatan aktivitas terbatas untuk menyediakan layanan dan menjaga keamanan.</p>
                    </section>
                    <section>
                        <h2 class="font-semibold text-slate-900">Penggunaan dan akses</h2>
                        <p>Jawaban digunakan untuk menampilkan respons kepada anggota workspace yang berwenang dan untuk fitur ekspor yang tersedia bagi owner/admin. Pengelola formulir menentukan tujuan pengumpulan, isi pertanyaan, pemberitahuan kepada responden, serta pihak yang diberi akses.</p>
                    </section>
                    <section>
                        <h2 class="font-semibold text-slate-900">Penyimpanan dan penghapusan</h2>
                        <p>Data disimpan pada database dan penyimpanan privat yang dikonfigurasi oleh operator Formora. Jadwal retensi dan penghapusan otomatis belum dikonfigurasi; pengelola layanan harus menetapkannya sebelum penggunaan produksi. Pengelola formulir harus memberi responden cara menghubungi pihak yang bertanggung jawab atas data.</p>
                    </section>
                    <section>
                        <h2 class="font-semibold text-slate-900">Kontak dan status dokumen</h2>
                        <p>Ini adalah pemberitahuan awal untuk pengujian, bukan kebijakan privasi final atau nasihat hukum. Operator layanan perlu mengganti informasi kontak dan meninjau dasar pemrosesan, masa simpan, hak subjek data, serta alur persetujuan sebelum membuka layanan untuk publik.</p>
                    </section>
                </div>
            </article>
        </main>
    </body>
</html>
