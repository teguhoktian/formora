<x-layouts::app :title="$template->title">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-5xl space-y-6">
            <a href="{{ route('templates.index') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">← Template Library</a>
            @if (session('status'))
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
            @endif

            <header class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800">{{ $template->category }}</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $template->kind === 'official' ? 'Template resmi' : ($template->visibility === 'public' ? 'Template komunitas' : 'Template workspace') }}</span>
                            @if ($template->status !== 'published')
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800">{{ match ($template->status) { 'pending' => 'Menunggu moderasi', 'rejected' => 'Perlu diperbaiki', 'archived' => 'Diarsipkan', default => 'Draf' } }}</span>
                            @endif
                        </div>
                        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">{{ $template->title }}</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">{{ $template->description ?: 'Preview struktur form dari template ini.' }}</p>
                        <p class="mt-3 text-xs text-slate-400">Versi {{ $template->latestVersion?->version_number ?? '—' }} · Kategori {{ $template->category }}</p>
                    </div>
                    @if ($canUse)
                        <form method="POST" action="{{ route('templates.use', $template->slug) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Gunakan template</button>
                        </form>
                    @endif
                </div>
                @if ($template->moderation_note && $canUpdate)
                    <p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"><strong>Catatan moderasi:</strong> {{ $template->moderation_note }}</p>
                @endif
            </header>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="template-preview-title">
                <div class="flex items-end justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h2 id="template-preview-title" class="text-lg font-semibold text-slate-950">Preview struktur form</h2>
                        <p class="mt-1 text-sm text-slate-500">Konten disalin ke form baru dan dapat diubah tanpa memengaruhi template.</p>
                    </div>
                </div>
                <div class="mt-5 space-y-5">
                    @forelse ($template->latestVersion?->schema['pages'] ?? [] as $page)
                        <article class="rounded-xl border border-slate-200 p-4 sm:p-5">
                            <h3 class="font-semibold text-slate-900">{{ $page['title'] }}</h3>
                            @if ($page['description'])<p class="mt-1 text-sm text-slate-500">{{ $page['description'] }}</p>@endif
                            <ol class="mt-4 space-y-2.5">
                                @foreach ($page['fields'] as $field)
                                    <li class="flex flex-col justify-between gap-1 rounded-lg bg-slate-50 px-3.5 py-3 sm:flex-row sm:items-center">
                                        <span class="text-sm font-medium text-slate-800">{{ $field['label'] }} @if ($field['required'])<span class="text-red-600" aria-label="wajib diisi">*</span>@endif</span>
                                        <span class="text-xs text-slate-500">{{ $field['type'] }}{{ in_array($field['type'], ['select', 'radio', 'checkbox'], true) ? ' · '.count($field['settings']['options'] ?? []).' pilihan' : '' }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </article>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Template ini belum memiliki halaman.</p>
                    @endforelse
                </div>
            </section>

            @if ($canUpdate)
                <section class="grid gap-6 lg:grid-cols-2">
                    <form method="POST" action="{{ route('templates.update', $template->slug) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        @csrf
                        @method('PUT')
                        <div>
                            <h2 class="font-semibold text-slate-950">Pengaturan template</h2>
                            <p class="mt-1 text-sm text-slate-500">Template publik diajukan ke moderator sebelum masuk katalog komunitas.</p>
                        </div>
                        <div>
                            <label for="template-edit-title" class="mb-1 block text-sm font-medium text-slate-700">Nama template</label>
                            <input id="template-edit-title" name="title" required maxlength="160" value="{{ old('title', $template->title) }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                        </div>
                        <div>
                            <label for="template-edit-description" class="mb-1 block text-sm font-medium text-slate-700">Deskripsi</label>
                            <textarea id="template-edit-description" name="description" rows="3" maxlength="2000" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">{{ old('description', $template->description) }}</textarea>
                        </div>
                        <div>
                            <label for="template-edit-category" class="mb-1 block text-sm font-medium text-slate-700">Kategori</label>
                            <input id="template-edit-category" name="category" required maxlength="80" value="{{ old('category', $template->category) }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                        </div>
                        <div>
                            <label for="template-visibility" class="mb-1 block text-sm font-medium text-slate-700">Visibilitas</label>
                            <select id="template-visibility" name="visibility" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                <option value="private" @selected(old('visibility', $template->visibility) === 'private')>Private · hanya pemilik dan admin workspace</option>
                                <option value="unlisted" @selected(old('visibility', $template->visibility) === 'unlisted')>Unlisted · hanya melalui tautan langsung</option>
                                <option value="public" @selected(old('visibility', $template->visibility) === 'public')>Public · ajukan ke komunitas untuk moderasi</option>
                            </select>
                        </div>
                        <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan pengaturan</button>
                    </form>

                    @if ($editableForms->isNotEmpty())
                        <form method="POST" action="{{ route('templates.versions.store', $template->slug) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            @csrf
                            <div>
                                <h2 class="font-semibold text-slate-950">Buat versi template</h2>
                                <p class="mt-1 text-sm text-slate-500">Simpan schema terbaru dari salah satu form workspace. Versi lama tetap tersedia.</p>
                            </div>
                            <div>
                                <label for="version-source-form" class="mb-1 block text-sm font-medium text-slate-700">Form sumber</label>
                                <select id="version-source-form" name="form_id" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                    <option value="">Pilih form</option>
                                    @foreach ($editableForms as $form)
                                        <option value="{{ $form->id }}">{{ $form->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan versi baru</button>
                        </form>
                    @endif
                </section>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="font-semibold text-slate-950">Riwayat versi</h2>
                <ol class="mt-3 divide-y divide-slate-100">
                    @foreach ($template->versions->sortByDesc('version_number') as $version)
                        <li class="flex justify-between gap-3 py-3 text-sm">
                            <span class="font-medium text-slate-700">Versi {{ $version->version_number }}</span>
                            <time class="text-slate-500">{{ $version->created_at?->format('d/m/Y H:i') }}</time>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</x-layouts::app>
