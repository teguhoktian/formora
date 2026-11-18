<x-layouts::app :title="__('Respons form')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex items-center gap-2 text-xs font-medium text-slate-400"><a href="{{ route('forms.index') }}" class="text-emerald-800 hover:text-emerald-900">Form saya</a><span aria-hidden="true">/</span><span>Respons</span></div>
            <x-page-header eyebrow="{{ $form->title }}" title="Respons" description="{{ number_format($submissions->total()) }} respons terkumpul · nilai sensitif dimasking.">
                @if ($canDownload)
                    <form method="POST" action="{{ route('forms.submissions.export', $form) }}">@csrf
                        @foreach ($filters as $key => $value) @if ($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
                        <button class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"><span aria-hidden="true">↓</span> Ekspor CSV</button>
                    </form>
                @endif
            </x-page-header>
            @if (session('status')) <p role="status" class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p> @endif
            <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_2px_12px_-8px_rgba(15,23,42,0.15)] sm:grid-cols-2 lg:grid-cols-[minmax(14rem,1fr)_11rem_11rem_11rem_auto] lg:items-end">
                <label class="grid gap-1.5 text-xs font-semibold text-slate-600">Cari jawaban / referensi<input name="search" value="{{ $filters['search'] }}" maxlength="100" placeholder="Ketik kata kunci…" class="rounded-lg border-slate-200 bg-white text-sm placeholder:text-slate-400 focus:border-emerald-600 focus:ring-emerald-600/20"></label>
                <label class="grid gap-1.5 text-xs font-semibold text-slate-600">Status<select name="status" class="rounded-lg border-slate-200 bg-white text-sm focus:border-emerald-600 focus:ring-emerald-600/20"><option value="">Semua status</option>@foreach (['new' => 'Baru', 'in_review' => 'Ditinjau', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'completed' => 'Selesai'] as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="grid gap-1.5 text-xs font-semibold text-slate-600">Dari tanggal<input type="date" name="from" value="{{ $filters['from'] }}" class="rounded-lg border-slate-200 text-sm focus:border-emerald-600 focus:ring-emerald-600/20"></label>
                <label class="grid gap-1.5 text-xs font-semibold text-slate-600">Sampai tanggal<input type="date" name="to" value="{{ $filters['to'] }}" class="rounded-lg border-slate-200 text-sm focus:border-emerald-600 focus:ring-emerald-600/20"></label>
                <button class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Terapkan</button>
            </form>
            @if ($canDownload && $exports->isNotEmpty())
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500" aria-live="polite">
                        <span class="font-medium text-slate-700">Riwayat ekspor</span>
                        <div class="flex flex-wrap gap-x-4 gap-y-2">
                        @foreach ($exports as $export)
                            <span class="text-xs">{{ $export->created_at->format('d/m/Y H:i') }} · @if ($export->status === 'ready') <a class="font-semibold text-emerald-800 underline underline-offset-2" href="{{ route('forms.submissions.exports.download', [$form, $export]) }}">Unduh</a> @else {{ $export->status === 'queued' ? 'menunggu antrean' : 'gagal' }} @endif</span>
                        @endforeach
                        </div>
                    </div>
            @endif
            <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_2px_12px_-8px_rgba(15,23,42,0.15)]">
                <div class="divide-y divide-slate-100">
                    @forelse ($submissions as $submission)
                        <a href="{{ route('forms.submissions.show', [$form, $submission]) }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-slate-50">
                            <span><strong class="block text-sm text-slate-900">{{ $submission->reference_number }}</strong><span class="text-xs text-slate-500">{{ $submission->created_at->format('d/m/Y H:i') }}</span></span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ ['new' => 'Baru', 'in_review' => 'Ditinjau', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'completed' => 'Selesai'][$submission->status] }}</span>
                        </a>
                    @empty
                        <p class="px-6 py-14 text-center text-sm text-slate-500">Belum ada respons yang cocok.</p>
                    @endforelse
                </div>
                @if ($submissions->hasPages()) <div class="border-t border-slate-100 px-5 py-4">{{ $submissions->links() }}</div> @endif
            </section>
        </div>
    </div>
</x-layouts::app>
