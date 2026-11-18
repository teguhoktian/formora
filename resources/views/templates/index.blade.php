<x-layouts::app :title="__('Template library')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-7xl space-y-7">
            <x-page-header eyebrow="Pustaka form" title="Template" description="Jelajahi template resmi dan komunitas, atau simpan form workspace untuk digunakan kembali.">
                @if ($canReview)
                    <a href="{{ route('templates.moderation') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">Moderasi komunitas</a>
                @endif
            </x-page-header>

            @if (session('status'))
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
            @endif

            @if ($editableForms->isNotEmpty())
                <details class="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_-8px_rgba(15,23,42,0.15)] sm:p-6">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-slate-900">Simpan form sebagai template <span class="grid size-8 place-items-center rounded-lg bg-slate-100 text-slate-500 transition group-open:rotate-45" aria-hidden="true">＋</span></summary>
                    <form method="POST" action="{{ route('templates.from-form') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label for="template-source-form" class="mb-1.5 block text-sm font-medium text-slate-700">Form sumber</label>
                            <select id="template-source-form" name="form_id" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                <option value="">Pilih form</option>
                                @foreach ($editableForms as $form)
                                    <option value="{{ $form->id }}" @selected(old('form_id') === $form->id)>{{ $form->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="template-title" class="mb-1.5 block text-sm font-medium text-slate-700">Nama template</label>
                            <input id="template-title" name="title" required maxlength="160" value="{{ old('title') }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                        </div>
                        <div>
                            <label for="template-category" class="mb-1.5 block text-sm font-medium text-slate-700">Kategori</label>
                            <input id="template-category" name="category" required maxlength="80" value="{{ old('category', 'Umum') }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                        </div>
                        <div>
                            <label for="template-description" class="mb-1.5 block text-sm font-medium text-slate-700">Deskripsi (opsional)</label>
                            <input id="template-description" name="description" maxlength="2000" value="{{ old('description') }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Simpan sebagai privat</button>
                        </div>
                    </form>
                </details>
            @endif

            <form method="GET" action="{{ route('templates.index') }}" class="grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_2px_12px_-8px_rgba(15,23,42,0.15)] sm:grid-cols-[1fr_14rem_auto]">
                <label class="sr-only" for="template-search">Cari template</label>
                <input id="template-search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau deskripsi template" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                <label class="sr-only" for="template-category-filter">Filter kategori</label>
                <select id="template-category-filter" name="category" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Cari template</button>
            </form>

            <section aria-label="Daftar template" class="space-y-4">
                @if ($templates->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($templates as $template)
                            <article class="group flex min-h-56 flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_-8px_rgba(15,23,42,0.15)] transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-emerald-700">{{ $template->category }}</p>
                                        <h2 class="mt-2 text-base font-semibold tracking-tight text-slate-950">{{ $template->title }}</h2>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">{{ $template->kind === 'official' ? 'Resmi' : ($template->visibility === 'public' ? 'Komunitas' : 'Workspace') }}</span>
                                </div>
                                <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-500">{{ $template->description ?: 'Template form siap disesuaikan.' }}</p>
                                <div class="mt-auto flex items-center justify-between pt-5">
                                    <span class="text-xs text-slate-400">{{ count($template->latestVersion?->schema['pages'] ?? []) }} halaman · v{{ $template->latestVersion?->version_number ?? 1 }}</span>
                                    <a href="{{ route('templates.show', $template->slug) }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-emerald-800 transition group-hover:bg-emerald-50">Lihat preview <span aria-hidden="true">→</span></a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @if ($templates->hasPages())
                        <div class="rounded-xl border border-slate-200 bg-white px-5 py-4">{{ $templates->links() }}</div>
                    @endif
                @else
                    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                        <h2 class="text-base font-semibold text-slate-950">Template tidak ditemukan</h2>
                        <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">Coba kata pencarian atau kategori lain. Template yang Anda simpan tersedia di library workspace.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts::app>
