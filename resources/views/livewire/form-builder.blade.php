<div class="min-h-screen bg-[#f7f8fa]" wire:key="form-builder-{{ $form->id }}">
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 shadow-[0_2px_10px_-8px_rgba(15,23,42,0.22)] backdrop-blur">
        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
            <a href="{{ route('forms.index') }}" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Kembali ke daftar form">←</a>
            <div class="min-w-0 flex-1">
                <label for="form-title" class="sr-only">Nama form</label>
                <input id="form-title" type="text" wire:model.live.debounce.700ms="formTitle" maxlength="160" class="w-full max-w-md truncate rounded-lg border border-transparent bg-transparent px-2 py-1 text-sm font-semibold text-slate-900 hover:border-slate-200 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                <p class="px-2 text-[11px] text-slate-400">Perubahan tersimpan otomatis</p>
            </div>

            <div class="flex items-center gap-2 text-xs" aria-live="polite">
                <span wire:loading class="text-amber-700">Menyimpan…</span>
                <span wire:loading.remove @class(['text-emerald-700' => $saveState === 'saved', 'text-red-700' => $saveState === 'error'])>
                    {{ $form->status === 'published' ? 'Dipublikasikan' : ($form->status === 'paused' ? 'Dijeda' : 'Draf') }} · {{ $saveState === 'saved' ? 'Tersimpan' : 'Belum tersimpan' }}
                </span>
            </div>
            @if ($canPublish)
                @if ($form->status === 'published')
                    <button type="button" wire:click="publishDraft('{{ $draftId }}')" wire:loading.attr="disabled" wire:confirm="Publikasikan draf sebagai versi publik terbaru?" class="rounded-xl bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">Publikasikan draf</button>
                    <button type="button" wire:click="pause" class="rounded-xl border border-amber-300 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-50">Jeda publik</button>
                    <button type="button" wire:click="unpublish" wire:confirm="Hentikan publikasi form ini? Versi publik sebelumnya tetap disimpan." class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Hentikan publikasi</button>
                @elseif ($form->status === 'paused')
                    <button type="button" wire:click="publishDraft('{{ $draftId }}')" wire:loading.attr="disabled" wire:confirm="Publikasikan draf sebagai versi publik terbaru?" class="rounded-xl bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">Publikasikan draf</button>
                    <button type="button" wire:click="resume" class="rounded-xl border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-50">Lanjutkan publikasi</button>
                    <button type="button" wire:click="unpublish" wire:confirm="Hentikan publikasi form ini? Versi publik sebelumnya tetap disimpan." class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Hentikan publikasi</button>
                @else
                    <button type="button" wire:click="publishDraft('{{ $draftId }}')" wire:loading.attr="disabled" wire:confirm="Publikasikan versi draf ini?" class="rounded-xl bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">Publikasikan</button>
                @endif
            @endif
            @if ($form->status === 'published' && $publicUrl)
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="rounded-xl border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-50">Lihat form publik</a>
            @endif
            <button type="button" wire:click="setTab('preview')" class="rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600">Pratinjau</button>
        </div>

        <nav class="mx-auto flex max-w-[1600px] items-center gap-1 overflow-x-auto px-4 sm:px-6" aria-label="Tab form builder">
            @foreach (['build' => 'Bangun', 'design' => 'Desain', 'logic' => 'Logika', 'settings' => 'Pengaturan', 'preview' => 'Pratinjau'] as $tab => $label)
                @if (in_array($tab, ['build', 'settings', 'preview'], true))
                    <button type="button" wire:click="setTab('{{ $tab }}')" @class([
                        'border-b-2 px-4 py-3 text-sm font-semibold',
                        'border-emerald-600 text-emerald-800' => $activeTab === $tab,
                        'border-transparent text-slate-500 hover:text-slate-800' => $activeTab !== $tab,
                    ]) aria-current="{{ $activeTab === $tab ? 'page' : 'false' }}">{{ $label }}</button>
                @else
                    <span class="cursor-not-allowed border-b-2 border-transparent px-4 py-3 text-sm text-slate-300" aria-disabled="true">{{ $label }} <span class="text-[9px] uppercase">Segera hadir</span></span>
                @endif
            @endforeach
        </nav>
    </header>

    @error('save')
        <div class="mx-auto mt-4 max-w-5xl px-4 sm:px-6" role="alert"><p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</p></div>
    @enderror
    @error('publish')
        <div class="mx-auto mt-4 max-w-5xl px-4 sm:px-6" role="alert"><p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</p></div>
    @enderror
    @if ($errors->any() && !$errors->has('save'))
        <div class="mx-auto mt-4 max-w-5xl px-4 sm:px-6" role="alert"><p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Periksa kembali isian. Perubahan belum tersimpan.</p></div>
    @endif

    @if ($activeTab === 'preview')
        <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:py-12">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
                <div class="mb-8 border-b border-slate-100 pb-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Pratinjau form</p>
                    <h1 class="mt-3 text-2xl font-semibold text-slate-950">{{ $form->title }}</h1>
                    @if ($form->description)<p class="mt-2 text-sm leading-6 text-slate-500">{{ $form->description }}</p>@endif
                </div>
                @foreach ($schema['pages'] as $page)
                    @if ($page['id'] === $activePage['id'])
                        <h2 class="mb-6 text-lg font-semibold text-slate-900">{{ $page['title'] }}</h2>
                        <div class="space-y-6">
                            @forelse ($page['fields'] as $field)
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-800">{{ $field['label'] }} @if ($field['required'])<span class="text-red-600" aria-label="wajib diisi">*</span>@endif</label>
                                    @if ($field['type'] === 'textarea')
                                        <textarea disabled rows="3" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5"></textarea>
                                    @elseif ($field['type'] === 'select')
                                        <select disabled class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5"><option>Pilih jawaban</option>@foreach ($field['settings']['options'] ?? [] as $option)<option>{{ $option['label'] }}</option>@endforeach</select>
                                    @elseif (in_array($field['type'], ['radio', 'checkbox'], true))
                                        <div class="space-y-2">
                                            @foreach ($field['settings']['options'] ?? [] as $option)
                                                <label class="flex items-center gap-2 text-sm text-slate-600"><input disabled type="{{ $field['type'] }}">{{ $option['label'] }}</label>
                                            @endforeach
                                        </div>
                                    @else
                                        <input disabled type="{{ match ($field['type']) { 'email' => 'email', 'phone' => 'tel', 'number' => 'number', 'date' => 'date', 'file' => 'file', default => 'text' } }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                                    @endif
                                    @if ($field['help_text'])<p class="mt-1.5 text-xs text-slate-500">{{ $field['help_text'] }}</p>@endif
                                </div>
                            @empty
                                <p class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Tambahkan field untuk melihat pratinjau.</p>
                            @endforelse
                        </div>
                        <button disabled class="mt-8 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white">{{ $schema['settings']['submit_label'] }}</button>
                    @endif
                @endforeach
                <p class="mt-5 text-xs text-slate-500">{{ $schema['settings']['success_message'] }}</p>
                <p class="mt-8 border-t border-slate-100 pt-4 text-center text-xs text-slate-400">Pratinjau saja · form belum dipublikasikan</p>
            </div>
        </main>
    @elseif ($activeTab === 'settings')
        <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:py-12">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-sm font-medium text-emerald-700">Pengaturan form</p>
                <h1 class="mt-2 text-xl font-semibold text-slate-950">Pesan responden</h1>
                <div class="mt-6 space-y-5">
                    <div>
                        <label for="submit-label" class="mb-1.5 block text-sm font-medium text-slate-700">Teks tombol kirim</label>
                        <input id="submit-label" wire:model.live.debounce.700ms="submitLabel" maxlength="80" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    </div>
                    <div>
                        <label for="success-message" class="mb-1.5 block text-sm font-medium text-slate-700">Pesan setelah form dikirim</label>
                        <textarea id="success-message" wire:model.live.debounce.700ms="successMessage" maxlength="500" rows="3" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"></textarea>
                    </div>
                </div>
            </section>
        </main>
    @else
        <main class="mx-auto grid max-w-[1600px] gap-4 px-4 py-5 sm:px-6 xl:grid-cols-[230px_minmax(420px,1fr)_290px]">
            <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <details class="group xl:open" open>
                    <summary class="cursor-pointer list-none text-sm font-semibold text-slate-900">Tambah field <span class="float-right text-slate-400 xl:hidden">⌄</span></summary>
                    <p class="mb-3 mt-1 text-xs text-slate-500">Pilih jenis field untuk ditambahkan.</p>
                    <div class="grid grid-cols-2 gap-2 xl:grid-cols-1">
                        @foreach ($fieldTypes as $type)
                            <button type="button" wire:click="addField('{{ $type }}')" class="flex min-w-0 items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-left text-xs font-medium text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600">
                                <span class="grid size-6 shrink-0 place-items-center rounded-lg bg-slate-100 text-[10px] font-bold uppercase text-slate-500" aria-hidden="true">{{ substr($type, 0, 2) }}</span>
                                <span class="truncate">{{ $smartFieldDefinitions[$type]['toolbox_label'] ?? match ($type) { 'text' => 'Teks singkat', 'textarea' => 'Paragraf', 'email' => 'Email', 'phone' => 'Telepon', 'number' => 'Angka', 'date' => 'Tanggal', 'select' => 'Dropdown', 'radio' => 'Pilihan tunggal', 'checkbox' => 'Kotak centang', 'file' => 'Unggah file' } }}</span>
                            </button>
                        @endforeach
                    </div>
                </details>
            </aside>

            <section class="min-w-0 rounded-2xl border border-slate-200 bg-slate-100/80 p-4 sm:p-6" aria-label="Canvas form">
                <div class="mx-auto max-w-2xl">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex min-w-0 flex-wrap gap-1.5" role="tablist" aria-label="Halaman form">
                            @foreach ($schema['pages'] as $page)
                                <button type="button" wire:click="selectPage('{{ $page['id'] }}')" @class([
                                    'rounded-lg px-3 py-2 text-xs font-semibold',
                                    'bg-white text-slate-900 shadow-sm' => $selectedPageId === $page['id'],
                                    'text-slate-500 hover:bg-white/70' => $selectedPageId !== $page['id'],
                                ]) role="tab" aria-selected="{{ $selectedPageId === $page['id'] ? 'true' : 'false' }}">{{ $page['title'] }}</button>
                            @endforeach
                        </div>
                        <div class="flex items-center gap-2">
                            @if (count($schema['pages']) > 1)
                                <button type="button" wire:click="deletePage('{{ $selectedPageId }}')" wire:confirm="Hapus halaman beserta semua field di dalamnya?" class="rounded-lg px-2 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">Hapus halaman</button>
                            @endif
                            <button type="button" wire:click="addPage" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">＋ Halaman</button>
                        </div>
                    </div>

                    <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <label for="page-title" class="sr-only">Judul halaman</label>
                        <input id="page-title" wire:model.live.debounce.700ms="pageTitle" maxlength="120" class="w-full border-0 p-0 text-xl font-semibold text-slate-950 placeholder:text-slate-400 focus:outline-none focus:ring-0" placeholder="Judul halaman">
                        <p class="mt-2 text-sm text-slate-400">Tambahkan pertanyaan ke halaman ini.</p>
                    </div>

                    <ul data-sortable-fields wire:ignore.self class="space-y-3" aria-label="Susun field form">
                        @forelse ($activePage['fields'] as $field)
                            <li wire:key="field-{{ $field['id'] }}" data-field-id="{{ $field['id'] }}" class="rounded-2xl border bg-white p-4 shadow-sm transition sm:p-5 {{ $selectedFieldId === $field['id'] ? 'border-emerald-500 ring-2 ring-emerald-500/15' : 'border-slate-200 hover:border-slate-300' }}">
                                <div class="flex items-start gap-3">
                                    <button type="button" data-drag-handle class="mt-0.5 cursor-grab rounded p-1 text-slate-400 hover:bg-slate-100 active:cursor-grabbing" aria-label="Geser untuk mengurutkan field">⠿</button>
                                    <button type="button" wire:click="selectField('{{ $field['id'] }}')" class="min-w-0 flex-1 text-left focus:outline-none">
                                        <span class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $smartFieldDefinitions[$field['type']]['label'] ?? $field['type'] }}</span>
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $field['label'] }} @if ($field['required'])<span class="text-red-600">*</span>@endif</span>
                                        @if ($field['help_text'])<span class="mt-1 block truncate text-xs text-slate-500">{{ $field['help_text'] }}</span>@endif
                                        @if (in_array($field['type'], ['select', 'radio', 'checkbox'], true))
                                            <span class="mt-2 block text-xs text-slate-400">{{ collect($field['settings']['options'] ?? [])->pluck('label')->join(' · ') }}</span>
                                        @else
                                            <span class="mt-3 block h-10 rounded-lg border border-slate-200 bg-slate-50"></span>
                                        @endif
                                    </button>
                                    <div class="flex shrink-0 items-center gap-1">
                                        <button type="button" wire:click="duplicateField('{{ $field['id'] }}')" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Duplikasi field">⧉</button>
                                        <button type="button" wire:click="deleteField('{{ $field['id'] }}')" wire:confirm="Hapus field ini?" class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-700" aria-label="Hapus field">×</button>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="rounded-2xl border-2 border-dashed border-slate-300 bg-white/70 px-6 py-14 text-center">
                                <span class="mx-auto grid size-11 place-items-center rounded-xl bg-slate-100 text-xl text-slate-500" aria-hidden="true">＋</span>
                                <p class="mt-3 text-sm font-semibold text-slate-800">Canvas masih kosong</p>
                                <p class="mt-1 text-xs text-slate-500">Pilih field dari toolbox untuk mulai menyusun form.</p>
                            </li>
                        @endforelse
                    </ul>
                    <p class="mt-3 text-center text-[11px] text-slate-400">Urutan otomatis tersimpan setelah Anda melepas field.</p>
                </div>
            </section>

            <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Pengaturan field">
                <h2 class="text-sm font-semibold text-slate-900">{{ $selectedField ? 'Pengaturan field' : 'Pengaturan halaman' }}</h2>
                @if ($selectedField)
                    <p class="mt-1 text-xs text-slate-500">{{ $smartFieldDefinitions[$selectedField['type']]['label'] ?? ucfirst($selectedField['type']) }} · {{ $selectedField['name'] }}</p>
                    @if (isset($smartFieldDefinitions[$selectedField['type']]))
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $smartFieldDefinitions[$selectedField['type']]['help_text'] }}</p>
                    @endif
                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="field-label" class="mb-1.5 block text-xs font-medium text-slate-600">Label</label>
                            <input id="field-label" wire:model.live.debounce.600ms="fieldLabel" maxlength="120" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                            @error('pages.*.fields.*.label')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="field-name" class="mb-1.5 block text-xs font-medium text-slate-600">Nama field</label>
                            <input id="field-name" wire:model.live.debounce.600ms="fieldName" maxlength="64" class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-xs focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                            <p class="mt-1 text-[10px] text-slate-400">Huruf kecil, angka, dan garis bawah.</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model.live="fieldRequired" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600"> Wajib diisi</label>
                        <div>
                            <label for="field-help" class="mb-1.5 block text-xs font-medium text-slate-600">Teks bantuan</label>
                            <textarea id="field-help" wire:model.live.debounce.600ms="fieldHelpText" maxlength="500" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20" placeholder="Petunjuk untuk responden"></textarea>
                        </div>
                        @if (in_array($selectedField['type'], ['select', 'radio', 'checkbox'], true))
                            <div>
                                <label for="field-options" class="mb-1.5 block text-xs font-medium text-slate-600">Pilihan <span class="font-normal text-slate-400">(satu per baris)</span></label>
                                <textarea id="field-options" wire:model.live.debounce.700ms="optionLines" rows="5" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"></textarea>
                            </div>
                        @endif
                        <div class="border-t border-slate-100 pt-3">
                            <button type="button" wire:click="duplicateField('{{ $selectedField['id'] }}')" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Duplikasi field</button>
                            <button type="button" wire:click="deleteField('{{ $selectedField['id'] }}')" wire:confirm="Hapus field ini?" class="mt-2 w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-red-700 hover:bg-red-50">Hapus field</button>
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-xs text-slate-500">Pilih halaman atau field untuk mengubah pengaturannya.</p>
                    <p class="mt-5 text-xs font-medium text-slate-600">{{ count($activePage['fields']) }} field di halaman ini</p>
                @endif
            </aside>
        </main>
    @endif

    @script
        <script>
            const fieldList = $wire.$el.querySelector('[data-sortable-fields]');
            if (fieldList && window.Sortable && !fieldList.dataset.sortableReady) {
                fieldList.dataset.sortableReady = 'true';
                new window.Sortable(fieldList, {
                    animation: 150,
                    handle: '[data-drag-handle]',
                    draggable: '[data-field-id]',
                    onEnd: () => {
                        const ids = Array.from(fieldList.querySelectorAll('[data-field-id]')).map((field) => field.dataset.fieldId);
                        $wire.reorderFields($wire.selectedPageId, ids);
                    },
                });
            }
        </script>
    @endscript
</div>
