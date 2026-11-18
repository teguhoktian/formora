<x-layouts::app :title="__('Moderasi template komunitas')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex items-center gap-2 text-xs font-medium text-slate-400"><a href="{{ route('templates.index') }}" class="text-emerald-800 hover:text-emerald-900">Template</a><span aria-hidden="true">/</span><span>Moderasi</span></div>
            <x-page-header eyebrow="Pustaka template" title="Moderasi komunitas" description="Periksa struktur form sebelum template komunitas ditampilkan." />
            @if (session('status'))
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
            @endif

            @if ($templates->isNotEmpty())
                <div class="space-y-4">
                    @foreach ($templates as $template)
                        <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_-8px_rgba(15,23,42,0.15)] sm:p-6">
                            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ $template->category }} · {{ $template->workspace?->name }}</p>
                                    <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ $template->title }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">Diajukan oleh {{ $template->creator?->name ?? 'Pengguna terhapus' }} · {{ $template->created_at?->format('d/m/Y H:i') }}</p>
                                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ $template->description }}</p>
                                </div>
                                <a href="{{ route('templates.show', $template->slug) }}" class="shrink-0 rounded-lg px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Buka preview</a>
                            </div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($template->latestVersion?->schema['pages'] ?? [] as $page)
                                    @foreach ($page['fields'] as $field)
                                            <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-3.5 py-3">
                                            <p class="text-sm font-medium text-slate-800">{{ $field['label'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $field['type'] }}{{ $field['required'] ? ' · Wajib' : '' }}</p>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                            <form method="POST" action="{{ route('templates.moderate', $template->slug) }}" class="mt-5 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[1fr_auto_auto]">
                                @csrf
                                <label class="sr-only" for="moderation-note-{{ $template->id }}">Catatan moderasi</label>
                                <input id="moderation-note-{{ $template->id }}" name="moderation_note" maxlength="2000" placeholder="Catatan (wajib jika ditolak)" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                <button name="decision" value="reject" class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Tolak</button>
                                <button name="decision" value="approve" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Setujui</button>
                            </form>
                        </article>
                    @endforeach
                </div>
                @if ($templates->hasPages())<div class="rounded-xl border border-slate-200 bg-white px-5 py-4">{{ $templates->links() }}</div>@endif
            @else
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                    <h2 class="font-semibold text-slate-950">Tidak ada template menunggu moderasi</h2>
                    <p class="mt-1 text-sm text-slate-500">Pengajuan komunitas baru akan muncul di sini.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
