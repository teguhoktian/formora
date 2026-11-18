<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Buat formulir digital dan kelola jawaban dalam satu ruang kerja bersama Formora.">
        <title>Formora — Formulir digital yang tertata</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#f7f8fa] text-slate-900 antialiased">
        <header class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10">
            <x-app-logo href="{{ route('home') }}" aria-label="Formora, beranda" wire:navigate />
            <nav class="flex items-center gap-2 sm:gap-3" aria-label="Navigasi utama">
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-white hover:text-slate-950 sm:px-4">Masuk</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">Buat akun <span aria-hidden="true">→</span></a>
            </nav>
        </header>

        <main>
            <section class="relative isolate overflow-hidden">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_78%_35%,rgba(16,185,129,0.11),transparent_33%),radial-gradient(ellipse_at_8%_0%,rgba(99,102,241,0.07),transparent_28%)]" aria-hidden="true"></div>
                <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 pb-20 pt-12 sm:px-8 sm:pb-24 sm:pt-16 lg:grid-cols-[0.88fr_1.12fr] lg:gap-12 lg:px-10 lg:pb-28 lg:pt-20">
                    <div class="max-w-xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3.5 py-1.5 text-xs font-semibold tracking-wide text-emerald-800 shadow-sm">
                            <span class="size-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            FORM DIGITAL, ALUR KERJA LEBIH JELAS
                        </p>
                        <h1 class="mt-6 text-4xl font-semibold leading-[1.08] tracking-[-0.045em] text-slate-950 sm:text-5xl lg:text-[3.65rem]">
                            Kumpulkan informasi. <span class="text-emerald-700">Rapikan pekerjaan.</span>
                        </h1>
                        <p class="mt-6 max-w-lg text-base leading-7 text-slate-600 sm:text-lg sm:leading-8">
                            Susun formulir, bagikan tautan, lalu tinjau semua jawaban dari satu ruang kerja yang mudah dipahami tim.
                        </p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-[0_8px_20px_-10px_rgba(4,120,87,0.65)] transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                                Mulai buat form <span aria-hidden="true">→</span>
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white">Saya sudah punya akun</a>
                        </div>
                        <div class="mt-9 flex flex-wrap gap-x-6 gap-y-3 text-xs font-medium text-slate-500">
                            <span class="inline-flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 text-[11px] text-emerald-800" aria-hidden="true">✓</span>Builder visual</span>
                            <span class="inline-flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 text-[11px] text-emerald-800" aria-hidden="true">✓</span>Respons terpusat</span>
                            <span class="inline-flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 text-[11px] text-emerald-800" aria-hidden="true">✓</span>Field Indonesia</span>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-[620px] lg:ml-auto" aria-label="Ilustrasi workspace Formora">
                        <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-br from-emerald-100/80 via-white to-indigo-100/70 blur-2xl" aria-hidden="true"></div>
                        <div class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_28px_80px_-36px_rgba(15,23,42,0.32)]">
                            <div class="flex items-center justify-between border-b border-slate-100 bg-white px-4 py-3.5 sm:px-5">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-8 place-items-center rounded-lg bg-slate-950 text-emerald-300"><x-app-logo-icon class="size-5 fill-current" /></span>
                                    <div><p class="text-xs font-semibold text-slate-900">Ruang kerja</p><p class="mt-0.5 text-[10px] text-slate-500">Tim Operasional</p></div>
                                </div>
                                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-800"><span class="mr-1.5 inline-block size-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>Aktif</span>
                            </div>
                            <div class="grid gap-5 bg-[#f8fafc] p-4 sm:grid-cols-[138px_1fr] sm:gap-6 sm:p-5">
                                <aside class="hidden space-y-1 sm:block" aria-hidden="true">
                                    <p class="mb-3 px-2 text-[9px] font-bold uppercase tracking-[0.16em] text-slate-400">Workspace</p>
                                    <div class="flex items-center gap-2 rounded-lg bg-emerald-50 px-2.5 py-2 text-[10px] font-semibold text-emerald-800"><span>▦</span> Ringkasan</div>
                                    <div class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-[10px] font-medium text-slate-500"><span>▤</span> Form saya</div>
                                    <div class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-[10px] font-medium text-slate-500"><span>▣</span> Respons</div>
                                    <div class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-[10px] font-medium text-slate-500"><span>◇</span> Template</div>
                                </aside>
                                <div class="min-w-0">
                                    <div class="flex items-end justify-between gap-3">
                                        <div><p class="text-[10px] font-medium text-emerald-700">RINGKASAN WORKSPACE</p><h2 class="mt-1 text-lg font-semibold tracking-tight text-slate-950 sm:text-xl">Selamat datang</h2></div>
                                        <span class="rounded-lg bg-slate-950 px-3 py-2 text-[10px] font-semibold text-white">＋ Buat form</span>
                                    </div>
                                    <div class="mt-4 grid grid-cols-3 gap-2 sm:gap-3">
                                        <div class="rounded-xl border border-slate-200 bg-white p-3"><p class="text-[9px] font-medium text-slate-500">Semua form</p><p class="mt-2 text-xl font-semibold text-slate-950">08</p></div>
                                        <div class="rounded-xl border border-slate-200 bg-white p-3"><p class="text-[9px] font-medium text-slate-500">Dipublikasi</p><p class="mt-2 text-xl font-semibold text-slate-950">05</p></div>
                                        <div class="rounded-xl border border-slate-200 bg-white p-3"><p class="text-[9px] font-medium text-slate-500">Respons</p><p class="mt-2 text-xl font-semibold text-slate-950">246</p></div>
                                    </div>
                                    <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                        <div class="flex items-center justify-between border-b border-slate-100 px-3.5 py-3"><div><p class="text-[11px] font-semibold text-slate-900">Form terbaru</p><p class="mt-0.5 text-[9px] text-slate-500">Pantau pekerjaan tim</p></div><span class="text-[9px] font-semibold text-emerald-800">Lihat semua →</span></div>
                                        <div class="space-y-0 divide-y divide-slate-100">
                                            <div class="flex items-center justify-between gap-2 px-3.5 py-3"><div class="flex min-w-0 items-center gap-2.5"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-indigo-50 text-xs text-indigo-700">▤</span><span class="truncate text-[10px] font-medium text-slate-800">Pendaftaran kegiatan</span></div><span class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-[8px] font-semibold text-emerald-800">Aktif</span></div>
                                            <div class="flex items-center justify-between gap-2 px-3.5 py-3"><div class="flex min-w-0 items-center gap-2.5"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-amber-50 text-xs text-amber-700">▤</span><span class="truncate text-[10px] font-medium text-slate-800">Survei kepuasan layanan</span></div><span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[8px] font-semibold text-slate-600">Draf</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-100 bg-white px-4 py-3 text-[9px] text-slate-400 sm:px-5"><span>Dirancang untuk alur kerja yang lebih tertata</span><span class="font-semibold tracking-wide text-slate-500">FORMORA</span></div>
                        </div>
                        <div class="absolute -bottom-5 -left-3 hidden rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-lg shadow-slate-900/5 sm:block">
                            <p class="text-[9px] font-medium text-slate-500">STATUS FORM</p><p class="mt-1.5 flex items-center gap-2 text-xs font-semibold text-slate-800"><span class="size-2 rounded-full bg-emerald-500" aria-hidden="true"></span>Siap menerima respons</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-y border-slate-200/80 bg-white/70">
                <div class="mx-auto grid max-w-7xl gap-8 px-5 py-9 sm:grid-cols-3 sm:px-8 lg:px-10">
                    <article class="flex gap-4"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-lg text-emerald-800" aria-hidden="true">✳</span><div><h2 class="text-sm font-semibold text-slate-950">Susun sesuai kebutuhan</h2><p class="mt-1 text-sm leading-6 text-slate-500">Atur pertanyaan dan halaman dengan builder visual.</p></div></article>
                    <article class="flex gap-4"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-lg text-indigo-700" aria-hidden="true">↗</span><div><h2 class="text-sm font-semibold text-slate-950">Bagikan dengan mudah</h2><p class="mt-1 text-sm leading-6 text-slate-500">Publikasikan form dan bagikan tautan kepada responden.</p></div></article>
                    <article class="flex gap-4"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-50 text-lg text-amber-700" aria-hidden="true">▤</span><div><h2 class="text-sm font-semibold text-slate-950">Tinjau dalam satu tempat</h2><p class="mt-1 text-sm leading-6 text-slate-500">Kelola respons, status, dan ekspor dari workspace Anda.</p></div></article>
                </div>
            </section>
        </main>

        <footer class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-7 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
            <span>© {{ date('Y') }} Formora</span>
            <a href="{{ route('privacy') }}" class="underline underline-offset-4 hover:text-slate-800">Pemberitahuan privasi</a>
        </footer>
    </body>
</html>
