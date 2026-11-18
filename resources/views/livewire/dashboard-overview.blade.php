<div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9" wire:poll.60s>
    <div class="mx-auto max-w-7xl space-y-8">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-emerald-700">Ruang kerja Anda</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Ringkasan</h1>
                <p class="mt-2 text-sm text-slate-500">Pantau form dan respons terbaru dalam satu tempat.</p>
            </div>
            <div class="flex items-center gap-3">
                <span wire:loading class="text-xs text-slate-500" role="status">Memperbarui data…</span>
                <button type="button" disabled title="Segera hadir" class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-500">
                    <span aria-hidden="true">＋</span> Buat form <span class="rounded-full bg-white/70 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">Segera hadir</span>
                </button>
            </div>
        </div>

        @if ($workspace)
            <section aria-label="Statistik workspace" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02]">
                    <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Semua form</p><span class="grid size-9 place-items-center rounded-xl bg-blue-50 text-blue-700" aria-hidden="true">▤</span></div>
                    <p class="mt-5 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($formCount) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Di workspace ini</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02]">
                    <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Form dipublikasikan</p><span class="grid size-9 place-items-center rounded-xl bg-emerald-50 text-emerald-700" aria-hidden="true">↗</span></div>
                    <p class="mt-5 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($publishedCount) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Siap menerima respons</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02]">
                    <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Total respons</p><span class="grid size-9 place-items-center rounded-xl bg-violet-50 text-violet-700" aria-hidden="true">↙</span></div>
                    <p class="mt-5 text-3xl font-semibold tracking-tight text-slate-950">{{ number_format($submissionCount) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Terkumpul dari semua form</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02]">
                    <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-500">Nama workspace</p><span class="grid size-9 place-items-center rounded-xl bg-amber-50 text-amber-700" aria-hidden="true">⌂</span></div>
                    <p class="mt-5 truncate text-xl font-semibold tracking-tight text-slate-950">{{ $workspace->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">Workspace aktif</p>
                </article>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.35fr_1fr]">
                <section id="my-forms" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.02]" aria-labelledby="forms-heading">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                        <div><h2 id="forms-heading" class="font-semibold text-slate-950">Form terbaru</h2><p class="mt-1 text-xs text-slate-500">Form yang terakhir diperbarui.</p></div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $formCount }} form</span>
                    </div>
                    @if ($recentForms->isEmpty())
                        <div class="px-6 py-12 text-center">
                            <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-xl text-slate-500" aria-hidden="true">▤</span>
                            <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum ada form</h3>
                            <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-slate-500">Form yang Anda buat akan muncul di sini. Form Builder sedang disiapkan.</p>
                        </div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($recentForms as $form)
                                <li class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                                    <div class="min-w-0"><p class="truncate text-sm font-medium text-slate-900">{{ $form->title }}</p><p class="mt-1 text-xs text-slate-500">Diperbarui {{ $form->updated_at?->format('d/m/Y') }}</p></div>
                                    <span @class([
                                        'shrink-0 rounded-full px-2.5 py-1 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700' => $form->status === 'published',
                                        'bg-slate-100 text-slate-600' => $form->status !== 'published',
                                    ])>{{ match ($form->status) { 'published' => 'Dipublikasikan', 'paused' => 'Dijeda', 'archived' => 'Diarsipkan', default => 'Draf' } }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section id="submissions" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.02]" aria-labelledby="submissions-heading">
                    <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 id="submissions-heading" class="font-semibold text-slate-950">Respons terbaru</h2><p class="mt-1 text-xs text-slate-500">Respons masuk ke workspace ini.</p></div>
                    @if ($recentSubmissions->isEmpty())
                        <div class="px-6 py-12 text-center">
                            <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-slate-100 text-xl text-slate-500" aria-hidden="true">↙</span>
                            <h3 class="mt-4 text-sm font-semibold text-slate-900">Belum ada respons</h3>
                            <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-slate-500">Respons terbaru akan tampil di sini setelah seseorang mengirim form.</p>
                        </div>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($recentSubmissions as $submission)
                                <li class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                                    <div class="min-w-0"><p class="truncate text-sm font-medium text-slate-900">{{ $submission->form?->title ?? 'Form dihapus' }}</p><p class="mt-1 text-xs text-slate-500">{{ $submission->created_at?->format('d/m/Y H:i') }}</p></div>
                                    <span class="shrink-0 text-xs text-slate-500">{{ match ($submission->status) { 'completed' => 'Selesai', 'in_review' => 'Ditinjau', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', default => 'Baru' } }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section aria-labelledby="activity-heading" class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.02]">
                    <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 id="activity-heading" class="font-semibold text-slate-950">Aktivitas terakhir</h2></div>
                    @if ($recentActivity->isEmpty())
                        <p class="px-6 py-8 text-sm text-slate-500">Aktivitas workspace akan dicatat dan ditampilkan di sini.</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($recentActivity as $activity)
                                <li class="flex items-start gap-3 px-5 py-4 sm:px-6"><span class="mt-1 size-2 rounded-full bg-emerald-500" aria-hidden="true"></span><div><p class="text-sm text-slate-700">{{ str($activity->event)->replace('_', ' ')->ucfirst() }}</p><p class="mt-1 text-xs text-slate-500">{{ $activity->created_at?->format('d/m/Y H:i') }}</p></div></li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section id="workspace" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6" aria-labelledby="workspace-heading">
                    <h2 id="workspace-heading" class="font-semibold text-slate-950">Workspace</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Buat workspace tambahan untuk memisahkan proyek atau tim.</p>
                    <form method="POST" action="{{ route('workspaces.store') }}" class="mt-5 flex flex-col gap-3 sm:flex-row">
                        @csrf
                        <label class="sr-only" for="workspace-name">Nama workspace</label>
                        <input id="workspace-name" name="name" type="text" maxlength="120" required placeholder="Contoh: Tim Operasional" value="{{ old('name') }}" class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                        <button type="submit" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Buat workspace</button>
                    </form>
                    @error('name') <p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </section>
            </div>
        @else
            <section class="rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-emerald-50 text-2xl text-emerald-700" aria-hidden="true">⌂</span>
                <h2 class="mt-5 text-lg font-semibold text-slate-950">Buat workspace pertama Anda</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Workspace menjadi tempat tim Anda mengelola form dan respons. Anda akan menjadi owner workspace yang dibuat.</p>
                <form method="POST" action="{{ route('workspaces.store') }}" class="mx-auto mt-6 flex max-w-md flex-col gap-3 sm:flex-row">
                    @csrf
                    <label class="sr-only" for="first-workspace-name">Nama workspace</label>
                    <input id="first-workspace-name" name="name" type="text" maxlength="120" required placeholder="Contoh: Tim Operasional" value="{{ old('name') }}" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    <button type="submit" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white">Buat workspace</button>
                </form>
                @error('name') <p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
            </section>
        @endif

        <p class="text-center text-xs text-slate-400">Data ringkasan diperbarui otomatis setiap menit.</p>
    </div>
</div>
