<x-layouts::app :title="__('Detail respons')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-4xl space-y-6">
            <div><a href="{{ route('forms.submissions.index', $form) }}" class="text-sm font-medium text-emerald-700">← Daftar respons</a><h1 class="mt-3 text-3xl font-semibold text-slate-950">{{ $submission->reference_number }}</h1><p class="mt-2 text-sm text-slate-500">Dikirim {{ $submission->created_at->format('d/m/Y H:i') }}</p></div>
            @if (session('status')) <p role="status" class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p> @endif
            <section class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white px-5">
                @forelse ($answers as $label => $value)
                    <div class="grid gap-1 py-4 sm:grid-cols-3"><dt class="text-sm font-medium text-slate-600">{{ $label }}</dt><dd class="whitespace-pre-wrap text-sm text-slate-900 sm:col-span-2">{{ is_array($value) ? implode(', ', array_map('strval', $value)) : $value }}</dd></div>
                @empty <p class="py-6 text-sm text-slate-500">Tidak ada jawaban.</p> @endforelse
            </section>
            @can('update', $form)
                <form method="POST" action="{{ route('forms.submissions.update', [$form, $submission]) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">@csrf @method('PATCH')
                    <h2 class="font-semibold text-slate-900">Tindak lanjut internal</h2>
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Status<select name="status" class="rounded-lg border-slate-300">@foreach (['new' => 'Baru', 'in_review' => 'Ditinjau', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'completed' => 'Selesai'] as $key => $label)<option value="{{ $key }}" @selected($submission->status === $key)>{{ $label }}</option>@endforeach</select></label>
                    <label class="grid gap-1 text-sm font-medium text-slate-700">Catatan internal<textarea name="internal_notes" rows="4" maxlength="5000" class="rounded-lg border-slate-300">{{ $submission->internal_notes }}</textarea></label>
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Simpan</button>
                </form>
            @endcan
        </div>
    </div>
</x-layouts::app>
