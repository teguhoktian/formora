<x-layouts::app :title="__('Respons form')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-7xl space-y-6">
            <div>
                <a href="{{ route('forms.index') }}" class="text-sm font-medium text-emerald-700">← Form saya</a>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Respons: {{ $form->title }}</h1>
                <p class="mt-2 text-sm text-slate-500">{{ $submissions->total() }} respons · nilai sensitif dimasking.</p>
            </div>
            @if (session('status')) <p role="status" class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p> @endif
            <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                <label class="grid gap-1 text-xs font-medium text-slate-600">Cari jawaban / referensi<input name="search" value="{{ $filters['search'] }}" maxlength="100" class="rounded-lg border-slate-300 text-sm"></label>
                <label class="grid gap-1 text-xs font-medium text-slate-600">Status<select name="status" class="rounded-lg border-slate-300 text-sm"><option value="">Semua</option>@foreach (['new' => 'Baru', 'in_review' => 'Ditinjau', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'completed' => 'Selesai'] as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="grid gap-1 text-xs font-medium text-slate-600">Dari tanggal<input type="date" name="from" value="{{ $filters['from'] }}" class="rounded-lg border-slate-300 text-sm"></label>
                <label class="grid gap-1 text-xs font-medium text-slate-600">Sampai tanggal<input type="date" name="to" value="{{ $filters['to'] }}" class="rounded-lg border-slate-300 text-sm"></label>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Terapkan</button>
            </form>
            @if ($canDownload)
                <form method="POST" action="{{ route('forms.submissions.export', $form) }}" class="-mt-4 flex justify-end">@csrf
                    @foreach ($filters as $key => $value) @if ($value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
                    <button class="rounded-lg border border-emerald-700 bg-white px-4 py-2 text-sm font-semibold text-emerald-800">Ekspor CSV terfilter</button>
                </form>
                @if ($exports->isNotEmpty())
                    <div class="-mt-3 space-y-2 text-right text-sm text-slate-500" aria-live="polite">
                        @foreach ($exports as $export)
                            <p>Ekspor {{ $export->created_at->format('d/m/Y H:i') }}: @if ($export->status === 'ready') <a class="font-medium text-emerald-800 underline" href="{{ route('forms.submissions.exports.download', [$form, $export]) }}">Unduh CSV</a> @else {{ $export->status === 'queued' ? 'menunggu antrean' : 'gagal' }} @endif</p>
                        @endforeach
                    </div>
                @endif
            @endif
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
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
