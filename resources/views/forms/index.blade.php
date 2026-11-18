<x-layouts::app :title="__('Form saya')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-7xl space-y-7">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">← Ringkasan</a>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Form saya</h1>
                    <p class="mt-2 text-sm text-slate-500">Kelola form di {{ $workspace?->name ?? 'workspace aktif' }}.</p>
                </div>
                @if ($canCreate)
                    <form method="POST" action="{{ route('forms.store') }}" class="flex flex-col gap-2 sm:flex-row">
                        @csrf
                        <label class="sr-only" for="new-form-title">Nama form baru</label>
                        <input id="new-form-title" name="title" required maxlength="160" value="{{ old('title') }}" placeholder="Nama form baru" class="min-w-0 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                        <button type="submit" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Buat form</button>
                    </form>
                @endif
            </div>

            @error('title') <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $message }}</p> @enderror
            @error('description') <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $message }}</p> @enderror

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="Daftar form">
                @if ($forms?->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach ($forms as $form)
                            <article class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                                <div class="min-w-0">
                                    <h2 class="truncate font-medium text-slate-900">{{ $form->title }}</h2>
                                    <p class="mt-1 truncate text-sm text-slate-500">{{ $form->description ?: 'Belum ada deskripsi' }}</p>
                                    <p class="mt-1 text-xs text-slate-400">Diperbarui {{ $form->updated_at?->format('d/m/Y H:i') }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700' => $form->status === 'published',
                                        'bg-slate-100 text-slate-600' => $form->status !== 'published',
                                    ])>{{ match ($form->status) { 'published' => 'Dipublikasikan', 'paused' => 'Dijeda', 'archived' => 'Diarsipkan', default => 'Draf' } }}</span>
                                    @can('update', $form)
                                        <a href="{{ route('forms.edit', $form) }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600">Buka builder</a>
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
                        <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-emerald-50 text-xl text-emerald-700" aria-hidden="true">▤</span>
                        <h2 class="mt-4 text-base font-semibold text-slate-950">Belum ada form</h2>
                        <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-slate-500">Buat form pertama untuk mulai menyusun pertanyaan dan mengumpulkan informasi.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts::app>
