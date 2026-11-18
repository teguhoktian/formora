<x-layouts::app :title="__('Template library')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-7xl space-y-7">
            <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">← Ringkasan</a>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Template Library</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Jelajahi template resmi dan komunitas, atau simpan form workspace sebagai template untuk digunakan kembali.</p>
                </div>
                @if ($canReview)
                    <a href="{{ route('templates.moderation') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Moderasi komunitas</a>
                @endif
            </header>

            @if (session('status'))
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
            @endif

            @if ($editableForms->isNotEmpty())
                <details class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <summary class="cursor-pointer list-none font-semibold text-slate-900">Simpan form sebagai template</summary>
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

            <form method="GET" action="{{ route('templates.index') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_14rem_auto]">
                <label class="sr-only" for="template-search">Cari template</label>
                <input id="template-search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau deskripsi template" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                <label class="sr-only" for="template-category-filter">Filter kategori</label>
                <select id="template-category-filter" name="category" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cari template</button>
            </form>

            <section aria-label="Daftar template" class="space-y-4">
                @if ($templates->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($templates as $template)
                            <article class="flex min-h-56 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ $template->category }}</p>
                                        <h2 class="mt-2 text-lg font-semibold text-slate-950">{{ $template->title }}</h2>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">{{ $template->kind === 'official' ? 'Resmi' : ($template->visibility === 'public' ? 'Komunitas' : 'Workspace') }}</span>
                                </div>
                                <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-500">{{ $template->description ?: 'Template form siap disesuaikan.' }}</p>
                                <div class="mt-auto flex items-center justify-between pt-5">
                                    <span class="text-xs text-slate-400">{{ count($template->latestVersion?->schema['pages'] ?? []) }} halaman · v{{ $template->latestVersion?->version_number ?? 1 }}</span>
                                    <a href="{{ route('templates.show', $template->slug) }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Lihat preview</a>
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
