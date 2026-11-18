<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Buat formulir digital dan kelola jawaban dengan lebih mudah bersama Formora.">
        <title>Formora — Form digital yang lebih tertata</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8">
            <a href="{{ route('home') }}" class="text-2xl font-bold tracking-tight text-indigo-700" aria-label="Formora, beranda">
                Formora<span class="text-teal-500">.</span>
            </a>
            <nav class="flex items-center gap-3" aria-label="Navigasi utama">
                <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Masuk
                </a>
                <a href="{{ route('register') }}" class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Daftar
                </a>
            </nav>
        </header>

        <main class="mx-auto flex max-w-7xl flex-col px-6 pb-20 pt-16 lg:px-8 lg:pb-28 lg:pt-24">
            <div class="max-w-3xl">
                <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-sm font-medium text-indigo-700 shadow-sm">
                    <span class="size-2 rounded-full bg-teal-500" aria-hidden="true"></span>
                    Formulir dan dokumen dalam satu ruang kerja
                </p>
                <h1 class="text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                    Form digital yang lebih tertata.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                    Buat formulir, kumpulkan jawaban, dan kelola submission dengan alur yang jelas. Formora membantu pekerjaan administratif terasa lebih sederhana.
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-4">
                    <a href="{{ route('register') }}" class="rounded-xl bg-indigo-700 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Mulai gratis
                    </a>
                    <a href="{{ route('login') }}" class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-base font-semibold text-slate-700 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Masuk ke akun
                    </a>
                </div>
            </div>

            <section class="mt-20 grid gap-8 border-t border-slate-200 pt-8 sm:grid-cols-3" aria-label="Kemampuan Formora">
                <div>
                    <h2 class="font-semibold text-slate-900">Buat formulir</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Susun kebutuhan informasi dalam formulir digital yang mudah dibagikan.</p>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-900">Kelola jawaban</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Kumpulkan respons dalam satu tempat agar mudah ditinjau dan ditindaklanjuti.</p>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-900">Siap berkembang</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Fondasi untuk template, dokumen, dan alur persetujuan di tahap berikutnya.</p>
                </div>
            </section>
        </main>

        <footer class="mx-auto max-w-7xl px-6 pb-8 text-sm text-slate-500 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span>© {{ date('Y') }} Formora</span>
                <a href="{{ route('privacy') }}" class="underline underline-offset-4 hover:text-slate-800">Pemberitahuan privasi</a>
            </div>
        </footer>
    </body>
</html>
