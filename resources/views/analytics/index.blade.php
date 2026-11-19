<x-layouts::app :title="__('Analitik')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-7xl space-y-7">
            <x-page-header eyebrow="{{ $workspace?->name ?? 'Workspace aktif' }}" title="Analitik" description="Ringkasan penggunaan form dan respons pada workspace aktif.">
                <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">Data diperbarui saat halaman dibuka</span>
            </x-page-header>

            @if ($workspace === null)
                <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                    <h2 class="text-lg font-semibold text-slate-900">Pilih workspace untuk melihat analitik</h2>
                    <p class="mt-2 text-sm text-slate-600">Bergabung atau buat workspace terlebih dahulu.</p>
                </section>
            @else
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan metrik">
                    @foreach ([['Total form', $formCount], ['Form dipublikasikan', $publishedCount], ['Total respons', $submissionCount], ['Respons 30 hari terakhir', $recentSubmissionCount]] as [$label, $value])
                        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($value, 0, ',', '.') }}</p>
                        </article>
                    @endforeach
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
                    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="trend-heading">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 id="trend-heading" class="text-base font-semibold text-slate-950">Tren respons</h2>
                                <p class="mt-1 text-sm text-slate-500">Enam bulan kalender terakhir</p>
                            </div>
                            <span class="text-xs text-slate-500">{{ number_format($monthlySubmissions->sum('count'), 0, ',', '.') }} respons</span>
                        </div>
                        <div class="mt-7 grid grid-cols-6 items-end gap-2 sm:gap-4" role="img" aria-label="Jumlah respons per bulan selama enam bulan terakhir">
                            @foreach ($monthlySubmissions as $month)
                                <div class="flex min-w-0 flex-col items-center gap-2">
                                    <span class="text-xs font-semibold text-slate-700">{{ number_format($month['count'], 0, ',', '.') }}</span>
                                    <div class="flex h-36 w-full items-end rounded-lg bg-slate-50 p-1">
                                        <div class="w-full rounded-md bg-emerald-600 transition-all" style="height: {{ max($month['count'] > 0 ? 8 : 0, (int) round(($month['count'] / $maxMonthlyCount) * 100)) }}%"></div>
                                    </div>
                                    <span class="truncate text-[10px] text-slate-500 sm:text-xs">{{ $month['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="status-heading">
                        <h2 id="status-heading" class="text-base font-semibold text-slate-950">Status respons</h2>
                        <p class="mt-1 text-sm text-slate-500">Distribusi seluruh respons workspace</p>
                        @php($statusLabels = ['new' => 'Baru', 'in_review' => 'Ditinjau', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'completed' => 'Selesai'])
                        <ul class="mt-5 divide-y divide-slate-100">
                            @forelse ($statusLabels as $status => $label)
                                <li class="flex items-center justify-between gap-3 py-3">
                                    <span class="flex items-center gap-2 text-sm text-slate-700"><span @class(['size-2 rounded-full', 'bg-sky-500' => $status === 'new', 'bg-amber-500' => $status === 'in_review', 'bg-emerald-500' => $status === 'approved', 'bg-red-500' => $status === 'rejected', 'bg-slate-400' => $status === 'completed']) aria-hidden="true"></span>{{ $label }}</span>
                                    <span class="text-sm font-semibold tabular-nums text-slate-900">{{ number_format((int) ($statusCounts[$status] ?? 0), 0, ',', '.') }}</span>
                                </li>
                            @empty
                                <li class="py-8 text-center text-sm text-slate-500">Belum ada respons.</li>
                            @endforelse
                        </ul>
                    </section>
                </div>

                <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm" aria-labelledby="top-forms-heading">
                    <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                        <h2 id="top-forms-heading" class="text-base font-semibold text-slate-950">Form dengan respons terbanyak</h2>
                        <p class="mt-1 text-sm text-slate-500">Maksimal lima form pada workspace ini</p>
                    </div>
                    @if ($topForms->isNotEmpty())
                        <ul class="divide-y divide-slate-100">
                            @foreach ($topForms as $form)
                                <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                                    <a href="{{ route('forms.submissions.index', $form) }}" class="min-w-0 truncate text-sm font-semibold text-emerald-800 hover:underline">{{ $form->title }}</a>
                                    <span class="text-sm tabular-nums text-slate-600">{{ number_format($form->submissions_count, 0, ',', '.') }} respons</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada form di workspace ini.</p>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-layouts::app>
