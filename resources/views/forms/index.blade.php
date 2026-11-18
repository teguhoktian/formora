<x-layouts::app :title="__('Form saya')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-7xl space-y-7">
            <x-page-header eyebrow="{{ $workspace?->name ?? 'Workspace aktif' }}" title="Form saya" description="Susun, publikasikan, dan kelola semua formulir workspace Anda.">
                @if ($canCreate)
                    <form method="POST" action="{{ route('forms.store') }}" class="flex w-full flex-col gap-2 rounded-xl border border-slate-200 bg-white p-2 shadow-sm sm:w-auto sm:flex-row">
                        @csrf
                        <label class="sr-only" for="new-form-title">Nama form baru</label>
                        <input id="new-form-title" name="title" required maxlength="160" value="{{ old('title') }}" placeholder="Nama form baru" class="min-w-0 rounded-lg border-0 bg-transparent px-3 py-2 text-sm placeholder:text-slate-400 focus:ring-2 focus:ring-emerald-600/20 sm:w-52">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"><span aria-hidden="true">＋</span> Buat form</button>
                    </form>
                @endif
            </x-page-header>

            @error('title') <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $message }}</p> @enderror
            @error('description') <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $message }}</p> @enderror

            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_2px_12px_-8px_rgba(15,23,42,0.15)]" aria-label="Daftar form">
                @if ($forms?->isNotEmpty())
                    <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-3 sm:px-6">
                        <p class="text-xs font-semibold text-slate-500">{{ $forms->total() }} formulir <span class="mx-1.5 text-slate-300">·</span> diurutkan dari yang terakhir diperbarui</p>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach ($forms as $form)
                            <article class="group flex flex-col gap-4 px-5 py-5 transition hover:bg-slate-50/70 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                <div class="flex min-w-0 items-start gap-3.5">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition group-hover:border-emerald-200 group-hover:text-emerald-700" aria-hidden="true">
                                        <svg viewBox="0 0 20 20" class="size-5" fill="none"><rect x="3.25" y="2.5" width="13.5" height="15" rx="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M6.5 7h7M6.5 10h7M6.5 13h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h2 class="truncate text-sm font-semibold text-slate-950">{{ $form->title }}</h2>
                                        <p class="mt-1 truncate text-sm text-slate-500">{{ $form->description ?: 'Tambahkan deskripsi untuk membantu tim mengenali form ini.' }}</p>
                                        <p class="mt-2 text-xs text-slate-400">Diperbarui {{ $form->updated_at?->format('d/m/Y · H:i') }}</p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 sm:justify-end sm:border-0 sm:pt-0">
                                    <span @class([
                                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                        'bg-emerald-50 text-emerald-800' => $form->status === 'published',
                                        'bg-amber-50 text-amber-800' => $form->status === 'paused',
                                        'bg-slate-100 text-slate-600' => ! in_array($form->status, ['published', 'paused'], true),
                                    ])><span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $form->status === 'published', 'bg-amber-500' => $form->status === 'paused', 'bg-slate-400' => ! in_array($form->status, ['published', 'paused'], true)]) aria-hidden="true"></span>{{ match ($form->status) { 'published' => 'Dipublikasikan', 'paused' => 'Dijeda', 'archived' => 'Diarsipkan', default => 'Draf' } }}</span>
                                    @can('update', $form)
                                        <a href="{{ route('forms.edit', $form) }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600">Buka builder <span aria-hidden="true">→</span></a>
                                    @endcan
                                    @can('viewSubmissions', $form)
                                        <a href="{{ route('forms.submissions.index', $form) }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Respons</a>
                                    @endcan
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @if ($forms->hasPages())
                        <div class="border-t border-slate-100 px-5 py-4">{{ $forms->links() }}</div>
                    @endif
                @else
                    <div class="px-6 py-16 text-center">
                        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-700" aria-hidden="true"><svg viewBox="0 0 24 24" class="size-7" fill="none"><rect x="4" y="3" width="16" height="18" rx="3" stroke="currentColor" stroke-width="1.6"/><path d="M8 9h8M8 13h8M8 17h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
                        <h2 class="mt-4 text-base font-semibold text-slate-950">Mulai dengan form pertama Anda</h2>
                        <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-slate-500">Susun pertanyaan, atur field yang dibutuhkan, lalu bagikan form kepada responden.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts::app>
