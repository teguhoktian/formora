<x-layouts::app :title="__('Respons')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-7 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-7xl space-y-6">
            <x-page-header eyebrow="{{ $workspace->name }}" title="Respons" description="Tinjau respons yang dapat Anda akses di workspace ini." />
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="responses-heading">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div><h2 id="responses-heading" class="font-semibold text-slate-950">Semua respons</h2><p class="mt-1 text-xs text-slate-500">Maksimal 25 respons per halaman.</p></div>
                    <form method="GET" action="{{ route('responses.index') }}" class="flex items-center gap-2">
                        <label for="response-status" class="sr-only">Filter status</label>
                        <select id="response-status" name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" onchange="this.form.requestSubmit()">
                            <option value="">Semua status</option>
                            @foreach (['new' => 'Baru', 'in_review' => 'Ditinjau', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'completed' => 'Selesai'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @if ($submissions->isEmpty())
                    <div class="px-6 py-14 text-center"><h3 class="text-sm font-semibold text-slate-900">Belum ada respons</h3><p class="mt-2 text-sm text-slate-500">Respons dari form workspace ini akan tampil di sini.</p><a href="{{ route('forms.index') }}" class="mt-4 inline-flex text-sm font-semibold text-emerald-800 hover:underline">Lihat form</a></div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($submissions as $submission)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                                <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $submission->form?->title ?? 'Form dihapus' }}</p><p class="mt-1 font-mono text-xs text-slate-500">{{ $submission->reference_number }} · {{ $submission->created_at?->format('d/m/Y H:i') }}</p></div>
                                <div class="flex items-center gap-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ match ($submission->status) { 'completed' => 'Selesai', 'in_review' => 'Ditinjau', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', default => 'Baru' } }}</span><a href="{{ route('forms.submissions.index', $submission->form_id) }}" class="text-sm font-semibold text-emerald-800 hover:underline">Buka form</a></div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="border-t border-slate-100 px-5 py-4 sm:px-6">{{ $submissions->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-layouts::app>
