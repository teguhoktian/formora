<div class="min-h-screen bg-[#f7f8fa]" wire:key="form-builder-{{ $form->id }}" x-data="{ confirmOpen: false, confirmMessage: '', confirmAction: null }" @formora-confirm.window="confirmMessage = $event.detail.message; confirmAction = $event.detail.action; confirmOpen = true" @keydown.escape.window="confirmOpen = false">
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
                    <button type="button" @click="$dispatch('formora-confirm', { message: 'Publikasikan draf sebagai versi publik terbaru?', action: () => $wire.publishDraft('{{ $draftId }}') })" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">Publikasikan draf</button>
                    <button type="button" wire:click="pause" class="rounded-xl border border-amber-300 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-50">Jeda publik</button>
                    <button type="button" @click="$dispatch('formora-confirm', { message: 'Hentikan publikasi form ini? Versi publik sebelumnya tetap disimpan.', action: () => $wire.unpublish() })" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Hentikan publikasi</button>
                @elseif ($form->status === 'paused')
                    <button type="button" @click="$dispatch('formora-confirm', { message: 'Publikasikan draf sebagai versi publik terbaru?', action: () => $wire.publishDraft('{{ $draftId }}') })" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">Publikasikan draf</button>
                    <button type="button" wire:click="resume" class="rounded-xl border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-50">Lanjutkan publikasi</button>
                    <button type="button" @click="$dispatch('formora-confirm', { message: 'Hentikan publikasi form ini? Versi publik sebelumnya tetap disimpan.', action: () => $wire.unpublish() })" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Hentikan publikasi</button>
                @else
                    <button type="button" @click="$dispatch('formora-confirm', { message: 'Publikasikan versi draf ini?', action: () => $wire.publishDraft('{{ $draftId }}') })" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">Publikasikan</button>
                @endif
            @endif
            @if ($form->status === 'published' && $publicUrl)
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="rounded-xl border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-50">Lihat form publik</a>
            @endif
            <button type="button" wire:click="setTab('preview')" class="rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600">Pratinjau</button>
        </div>

        <nav class="mx-auto flex max-w-[1600px] items-center gap-1 overflow-x-auto px-4 sm:px-6" aria-label="Tab form builder">
            @foreach (['build' => 'Bangun', 'design' => 'Desain', 'logic' => 'Logika', 'settings' => 'Pengaturan', 'preview' => 'Pratinjau'] as $tab => $label)
                @if (in_array($tab, ['build', 'design', 'logic', 'settings', 'preview'], true))
                    <button type="button" wire:click="setTab('{{ $tab }}')" @class([
                        'border-b-2 px-4 py-3 text-sm font-semibold',
                        'border-emerald-600 text-emerald-800' => $activeTab === $tab,
                        'border-transparent text-slate-500 hover:text-slate-800' => $activeTab !== $tab,
                    ]) aria-current="{{ $activeTab === $tab ? 'page' : 'false' }}">{{ $label }}</button>
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
        <div class="mx-auto mt-4 max-w-5xl px-4 sm:px-6" role="alert"><div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><p class="font-semibold">Periksa kembali isian. Perubahan belum tersimpan.</p>@foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach</div></div>
    @endif

    @if ($activeTab === 'logic')
        <main class="mx-auto grid max-w-5xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)] lg:py-12">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="logic-fields-heading">
                <p class="text-sm font-medium text-emerald-700">Aturan tampilan</p>
                <h1 id="logic-fields-heading" class="mt-1 text-xl font-semibold text-slate-950">Pilih field</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Pilih field yang hanya ditampilkan saat responden memilih jawaban tertentu.</p>
                <ul class="mt-5 space-y-2">
                    @forelse ($activePage['fields'] as $field)
                        <li><button type="button" wire:click="selectField('{{ $field['id'] }}')" @class(['w-full rounded-xl border px-3 py-3 text-left', 'border-emerald-300 bg-emerald-50' => $selectedFieldId === $field['id'], 'border-slate-200 hover:bg-slate-50' => $selectedFieldId !== $field['id']])><span class="block text-sm font-semibold text-slate-800">{{ $field['label'] }}</span><span class="mt-1 block text-xs text-slate-500">{{ $field['name'] }} · {{ $field['type'] }}</span></button></li>
                    @empty
                        <li class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Tambahkan field terlebih dahulu.</li>
                    @endforelse
                </ul>
            </section>
            <section wire:key="logic-rule-{{ $selectedFieldId ?? 'none' }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="logic-rule-heading">
                <h2 id="logic-rule-heading" class="text-lg font-semibold text-slate-950">Kondisi field</h2>
                @if ($selectedField)
                    @php
                        $visibilitySource = collect($conditionFields)->firstWhere('id', $visibilityFieldId);
                    @endphp
                    <p class="mt-1 text-sm text-slate-500">Atur kapan “{{ $selectedField['label'] }}” terlihat. Aturan saat ini hanya memakai field pilihan pada halaman yang sama.</p>
                    @if (count($conditionFields) > 0)
                        <div class="mt-6 space-y-4">
                            <div><label for="visibility-source" class="mb-1.5 block text-sm font-medium text-slate-700">Tampilkan jika jawaban pada</label><select id="visibility-source" wire:key="visibility-source-{{ $selectedFieldId }}" wire:model.live="visibilityFieldId" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm"><option value="">Selalu tampil</option>@foreach ($conditionFields as $conditionField)<option value="{{ $conditionField['id'] }}">{{ $conditionField['label'] }}</option>@endforeach</select></div>
                            @if ($visibilityFieldId !== '' && $visibilitySource)
                                <div><label for="visibility-value" class="mb-1.5 block text-sm font-medium text-slate-700">sama dengan</label><select id="visibility-value" wire:key="visibility-value-{{ $selectedFieldId }}-{{ $visibilityFieldId }}" wire:model.live="visibilityValue" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm"><option value="">Pilih jawaban</option>@foreach ($visibilitySource['settings']['options'] as $option)<option value="{{ $option['value'] }}">{{ $option['label'] }}</option>@endforeach</select></div>
                            @endif
                            <button type="button" wire:click="saveVisibilityCondition" wire:loading.attr="disabled" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">Simpan aturan</button>
                            @if (is_array($selectedField['visibility'] ?? null))<p class="text-xs text-slate-500">Field ini saat ini tampil jika pilihan tersebut cocok.</p>@else<p class="text-xs text-slate-500">Tanpa aturan, field selalu tampil.</p>@endif
                        </div>
                    @else
                        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">Tambahkan field Dropdown atau Pilihan tunggal pada halaman ini sebagai pemicu. Field pemicu harus selalu terlihat.</div>
                    @endif
                @else
                    <p class="mt-3 text-sm text-slate-500">Pilih field di sebelah kiri untuk mengatur kondisi tampilnya.</p>
                @endif
            </section>
        </main>
    @elseif ($activeTab === 'design')
        <main class="mx-auto grid max-w-5xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.9fr)] lg:py-12">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-sm font-medium text-emerald-700">Tampilan form</p>
                <h1 class="mt-2 text-xl font-semibold text-slate-950">Atur gaya form publik</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Perubahan tersimpan otomatis dan digunakan pada tautan form publik. Pilihan warna dibatasi agar kontras tetap terbaca.</p>
                <div class="mt-6 space-y-5">
                    <div><label for="design-accent" class="mb-1.5 block text-sm font-medium text-slate-700">Warna aksen</label><select id="design-accent" wire:model.live="designAccent" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm"><option value="emerald">Hijau</option><option value="blue">Biru</option><option value="violet">Ungu</option></select></div>
                    <div><label for="design-width" class="mb-1.5 block text-sm font-medium text-slate-700">Lebar konten</label><select id="design-width" wire:model.live="designWidth" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm"><option value="compact">Ringkas</option><option value="standard">Standar</option><option value="wide">Lebar</option></select></div>
                </div>
            </section>
            @php
                $previewAccent = match ($designAccent) { 'blue' => ['bg' => 'bg-blue-700', 'text' => 'text-blue-800'], 'violet' => ['bg' => 'bg-violet-700', 'text' => 'text-violet-800'], default => ['bg' => 'bg-emerald-700', 'text' => 'text-emerald-800'] };
                $previewWidth = match ($designWidth) { 'compact' => 'max-w-xs', 'wide' => 'max-w-2xl', default => 'max-w-md' };
            @endphp
            <section class="rounded-2xl border border-slate-200 bg-slate-100 p-4 sm:p-6" aria-label="Contoh tampilan">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Contoh</p>
                <div class="{{ $previewWidth }} mx-auto overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 p-5"><p class="text-xs font-semibold {{ $previewAccent['text'] }}">FORMORA</p><h2 class="mt-2 text-lg font-semibold text-slate-950">{{ $form->title }}</h2></div><div class="space-y-4 p-5"><div><p class="mb-1 text-sm font-medium text-slate-700">Nama lengkap</p><div class="h-10 rounded-lg border border-slate-300 bg-white"></div></div><button type="button" class="rounded-lg {{ $previewAccent['bg'] }} px-4 py-2 text-sm font-semibold text-white">{{ $submitLabel }}</button></div></div>
            </section>
        </main>
    @elseif ($activeTab === 'preview')
        @php
            $previewAccent = match ($designAccent) { 'blue' => ['bg' => 'bg-blue-700', 'text' => 'text-blue-800'], 'violet' => ['bg' => 'bg-violet-700', 'text' => 'text-violet-800'], default => ['bg' => 'bg-emerald-700', 'text' => 'text-emerald-800'] };
            $previewWidth = match ($designWidth) { 'compact' => 'max-w-xl', 'wide' => 'max-w-5xl', default => 'max-w-3xl' };
        @endphp
        <main class="mx-auto {{ $previewWidth }} px-4 py-8 sm:px-6 lg:py-12">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
                <div class="mb-8 border-b border-slate-100 pb-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] {{ $previewAccent['text'] }}">Pratinjau form</p>
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
                                    @elseif ($field['type'] === 'rating')
                                        <div class="text-2xl tracking-wide text-amber-400" aria-label="Rating bintang">{{ str_repeat('★ ', $field['settings']['scale']) }}</div>
                                    @elseif ($field['type'] === 'slider')
                                        <div class="space-y-2"><input disabled type="range" min="{{ $field['settings']['min'] }}" max="{{ $field['settings']['max'] }}" step="{{ $field['settings']['step'] }}" class="w-full"><div class="flex justify-between text-xs text-slate-400"><span>{{ $field['settings']['min'] }}</span><span>{{ $field['settings']['max'] }}</span></div></div>
                                    @elseif ($field['type'] === 'signature')
                                        <div class="grid h-24 place-items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-xs text-slate-400">Area tanda tangan responden</div>
                                    @elseif ($field['type'] === 'date' && ($field['settings']['mode'] ?? 'date') === 'date_range')
                                        <div class="grid grid-cols-2 gap-2"><div class="h-10 rounded-lg border border-slate-300 bg-slate-50"></div><div class="h-10 rounded-lg border border-slate-300 bg-slate-50"></div></div>
                                    @elseif (in_array($field['type'], ['radio', 'checkbox'], true))
                                        <div class="space-y-2">
                                            @foreach ($field['settings']['options'] ?? [] as $option)
                                                <label class="flex items-center gap-2 text-sm text-slate-600"><input disabled type="{{ $field['type'] }}">{{ $option['label'] }}</label>
                                            @endforeach
                                        </div>
                                    @else
                                        <input disabled type="{{ match ($field['type']) { 'email' => 'email', 'phone' => 'tel', 'number' => 'number', 'date' => match ($field['settings']['mode'] ?? 'date') { 'time' => 'time', 'datetime' => 'datetime-local', default => 'date' }, 'file' => 'file', default => 'text' } }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5">
                                    @endif
                                    @if ($field['help_text'])<p class="mt-1.5 text-xs text-slate-500">{{ $field['help_text'] }}</p>@endif
                                </div>
                            @empty
                                <p class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Tambahkan field untuk melihat pratinjau.</p>
                            @endforelse
                        </div>
                        <button disabled class="mt-8 rounded-xl {{ $previewAccent['bg'] }} px-5 py-3 text-sm font-semibold text-white">{{ $schema['settings']['submit_label'] }}</button>
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
                <h1 class="mt-2 text-xl font-semibold text-slate-950">Detail form dan pesan responden</h1>
                <div class="mt-6 space-y-5">
                    <div>
                        <label for="form-description" class="mb-1.5 block text-sm font-medium text-slate-700">Deskripsi form</label>
                        <textarea id="form-description" wire:model.live.debounce.700ms="formDescription" maxlength="2000" rows="4" placeholder="Jelaskan tujuan form dan informasi penting untuk responden." class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"></textarea>
                        @error('formDescription')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                    </div>
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
            <aside x-data="{ fieldCatalogOpen: false, fieldSearch: '' }" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <details class="group xl:open" open>
                    <summary class="cursor-pointer list-none text-sm font-semibold text-slate-900">Tambah field <span class="float-right text-slate-400 xl:hidden">⌄</span></summary>
                    <p class="mb-3 mt-1 text-xs text-slate-500">Pilih jenis field untuk ditambahkan.</p>
                    <div class="grid grid-cols-2 gap-2 xl:grid-cols-1">
                        @foreach (array_slice($fieldTypes, 0, 8) as $type)
                            <button type="button" wire:click="addField('{{ $type }}')" class="flex min-w-0 items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-left text-xs font-medium text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600">
                                <span class="grid size-6 shrink-0 place-items-center rounded-lg bg-slate-100 text-[10px] font-bold uppercase text-slate-500" aria-hidden="true">{{ substr($type, 0, 2) }}</span>
                                <span class="truncate">{{ $smartFieldDefinitions[$type]['toolbox_label'] ?? match ($type) { 'text' => 'Teks singkat', 'textarea' => 'Paragraf', 'email' => 'Email', 'phone' => 'Telepon', 'number' => 'Angka', 'date' => 'Tanggal', 'rating' => 'Rating bintang', 'slider' => 'Slider angka', 'select' => 'Dropdown', 'radio' => 'Pilihan tunggal', 'checkbox' => 'Kotak centang', 'file' => 'Unggah file' } }}</span>
                            </button>
                        @endforeach
                    </div>
                    <button type="button" @click="fieldCatalogOpen = true; fieldSearch = ''" class="mt-3 w-full rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">Cari semua field</button>
                </details>
                <div x-cloak x-show="fieldCatalogOpen" x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4" role="presentation" @click.self="fieldCatalogOpen = false" @keydown.escape.window="fieldCatalogOpen = false">
                    <section x-show="fieldCatalogOpen" x-transition class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="field-catalog-title">
                        <header class="border-b border-slate-100 p-5"><div class="flex items-center justify-between"><div><h2 id="field-catalog-title" class="text-lg font-semibold text-slate-950">Pilih field</h2><p class="mt-1 text-xs text-slate-500">Cari berdasarkan nama atau jenis data.</p></div><button type="button" @click="fieldCatalogOpen = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Tutup">×</button></div><input x-model="fieldSearch" x-ref="fieldSearchInput" type="search" placeholder="Cari field…" class="mt-4 w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"></header>
                        <div x-effect="if (fieldCatalogOpen) $nextTick(() => $refs.fieldSearchInput.focus())" class="grid max-h-[60vh] grid-cols-1 gap-2 overflow-y-auto p-4 sm:grid-cols-2">
                            @foreach ($fieldTypes as $type)
                                <button type="button" wire:key="field-catalog-{{ $type }}" x-show="!fieldSearch || @js(mb_strtolower(($smartFieldDefinitions[$type]['toolbox_label'] ?? match ($type) { 'date' => 'tanggal', 'signature' => 'tanda tangan', default => $type }).' '.$type)).includes(fieldSearch.toLowerCase())" @click="$wire.addField('{{ $type }}'); fieldCatalogOpen = false" class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-left hover:border-emerald-300 hover:bg-emerald-50"><span class="grid size-9 place-items-center rounded-lg bg-slate-100 text-xs font-bold uppercase text-slate-600">{{ substr($type, 0, 2) }}</span><span><span class="block text-sm font-semibold text-slate-800">{{ $smartFieldDefinitions[$type]['toolbox_label'] ?? match ($type) { 'text' => 'Teks singkat', 'textarea' => 'Paragraf', 'email' => 'Email', 'phone' => 'Telepon', 'number' => 'Angka', 'date' => 'Tanggal', 'select' => 'Dropdown', 'radio' => 'Pilihan tunggal', 'checkbox' => 'Kotak centang', 'file' => 'Unggah file', 'signature' => 'Tanda tangan', default => ucfirst($type) } }}</span><span class="text-[11px] text-slate-500">{{ $type }}</span></span></button>
                            @endforeach
                            @foreach (['time' => 'Jam', 'datetime' => 'Tanggal dan waktu', 'date_range' => 'Rentang tanggal'] as $mode => $label)
                                <button type="button" wire:key="field-date-mode-{{ $mode }}" x-show="!fieldSearch || @js(mb_strtolower($label.' tanggal date')).includes(fieldSearch.toLowerCase())" @click="$wire.addDateField('{{ $mode }}'); fieldCatalogOpen = false" class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-left hover:border-emerald-300 hover:bg-emerald-50"><span class="grid size-9 place-items-center rounded-lg bg-slate-100 text-xs font-bold uppercase text-slate-600">{{ $mode === 'datetime' ? 'dt' : ($mode === 'date_range' ? 'dr' : 'jm') }}</span><span><span class="block text-sm font-semibold text-slate-800">{{ $label }}</span><span class="text-[11px] text-slate-500">Field tanggal</span></span></button>
                            @endforeach
                        </div>
                        <footer class="border-t border-slate-100 p-4 text-right"><button type="button" @click="fieldCatalogOpen = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Tutup</button></footer>
                    </section>
                </div>
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
                                <button type="button" @click="$dispatch('formora-confirm', { message: 'Hapus halaman beserta semua field di dalamnya?', action: () => $wire.deletePage('{{ $selectedPageId }}') })" class="rounded-lg px-2 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">Hapus halaman</button>
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
                                        <button type="button" @click="$dispatch('formora-confirm', { message: 'Hapus field ini?', action: () => $wire.deleteField('{{ $field['id'] }}') })" class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-700" aria-label="Hapus field">×</button>
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
                        @if ($selectedField['type'] === 'date')
                            <div class="border-t border-slate-100 pt-3"><label for="date-mode" class="mb-1.5 block text-xs font-medium text-slate-600">Jenis tanggal</label><select id="date-mode" wire:model="fieldSettings.mode" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"><option value="date">Tanggal</option><option value="time">Jam</option><option value="datetime">Tanggal dan jam</option><option value="date_range">Rentang tanggal</option></select><button type="button" wire:click="saveFieldPresentation" class="mt-2 w-full rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800">Simpan jenis tanggal</button></div>
                        @elseif ($selectedField['type'] === 'rating')
                            <div class="border-t border-slate-100 pt-3"><label for="rating-scale" class="mb-1.5 block text-xs font-medium text-slate-600">Jumlah bintang</label><select id="rating-scale" wire:model="fieldSettings.scale" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">@for ($scale = 3; $scale <= 10; $scale++)<option value="{{ $scale }}">{{ $scale }} bintang</option>@endfor</select><button type="button" wire:click="saveFieldPresentation" class="mt-2 w-full rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800">Simpan skala</button></div>
                        @elseif ($selectedField['type'] === 'slider')
                            <div class="space-y-2 border-t border-slate-100 pt-3"><p class="text-xs font-medium text-slate-600">Rentang slider</p><div class="grid grid-cols-3 gap-1.5"><label class="text-[10px] text-slate-500">Min<input type="number" wire:model="fieldSettings.min" class="mt-1 w-full rounded border border-slate-300 px-1.5 py-1.5 text-xs"></label><label class="text-[10px] text-slate-500">Maks<input type="number" wire:model="fieldSettings.max" class="mt-1 w-full rounded border border-slate-300 px-1.5 py-1.5 text-xs"></label><label class="text-[10px] text-slate-500">Langkah<input type="number" min="0.01" step="any" wire:model="fieldSettings.step" class="mt-1 w-full rounded border border-slate-300 px-1.5 py-1.5 text-xs"></label></div><button type="button" wire:click="saveFieldPresentation" class="w-full rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800">Simpan rentang</button></div>
                        @endif
                        @if (in_array($selectedField['type'], ['select', 'radio', 'checkbox'], true))
                            <div>
                                <label for="field-options" class="mb-1.5 block text-xs font-medium text-slate-600">Pilihan <span class="font-normal text-slate-400">(satu per baris)</span></label>
                                <textarea id="field-options" wire:model.live.debounce.700ms="optionLines" rows="5" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"></textarea>
                            </div>
                        @endif
                        <section wire:key="field-validation-{{ $selectedField['id'] }}" class="space-y-3 border-t border-slate-100 pt-4" aria-labelledby="field-validation-title">
                            <h3 id="field-validation-title" class="text-xs font-semibold text-slate-800">Validasi jawaban</h3>
                            @if (in_array($selectedField['type'], ['text', 'textarea'], true))
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="text-[11px] text-slate-600">Panjang minimum<input type="number" min="0" max="{{ $selectedField['type'] === 'textarea' ? 20000 : 5000 }}" wire:model="fieldValidation.min_length" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label>
                                    <label class="text-[11px] text-slate-600">Panjang maksimum<input type="number" min="0" max="{{ $selectedField['type'] === 'textarea' ? 20000 : 5000 }}" wire:model="fieldValidation.max_length" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label>
                                </div>
                                <label class="block text-[11px] text-slate-600">Harus berisi<input type="text" maxlength="200" wire:model="fieldValidation.contains" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label>
                                <label class="block text-[11px] text-slate-600">Tidak boleh berisi<input type="text" maxlength="200" wire:model="fieldValidation.not_contains" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label>
                                <div class="space-y-2 rounded-lg bg-slate-50 p-2.5">
                                    <label class="block text-[11px] text-slate-600">Pola teks (opsional)<input type="text" maxlength="128" wire:model="fieldValidation.pattern" placeholder="Contoh: [0-9]{10}" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 font-mono text-xs"></label>
                                    <select wire:model="fieldValidation.pattern_mode" aria-label="Jenis pencocokan pola" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"><option value="matches">Harus cocok seluruhnya</option><option value="contains">Harus cocok sebagian</option><option value="not_matches">Tidak boleh cocok</option><option value="not_contains">Tidak boleh cocok sebagian</option></select>
                                    <p class="text-[10px] leading-4 text-slate-500">Pola dibatasi ke sintaks sederhana demi keamanan.</p>
                                </div>
                            @elseif ($selectedField['type'] === 'number')
                                <select wire:model="fieldValidation.operator" aria-label="Aturan angka" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs"><option value="">Tanpa batas tambahan</option><option value="between">Di antara</option><option value="equal">Sama dengan</option><option value="greater_than">Lebih besar dari</option><option value="at_least">Lebih besar atau sama dengan</option><option value="less_than">Lebih kecil dari</option><option value="at_most">Lebih kecil atau sama dengan</option></select>
                                @if (($fieldValidation['operator'] ?? '') === 'between')
                                    <div class="grid grid-cols-2 gap-2"><label class="text-[11px] text-slate-600">Minimum<input type="number" step="any" wire:model="fieldValidation.min" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label><label class="text-[11px] text-slate-600">Maksimum<input type="number" step="any" wire:model="fieldValidation.max" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label></div>
                                @elseif (($fieldValidation['operator'] ?? '') !== '')
                                    <label class="block text-[11px] text-slate-600">Nilai pembanding<input type="number" step="any" wire:model="fieldValidation.value" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label>
                                @endif
                            @elseif ($selectedField['type'] === 'date')
                                <label class="block text-[11px] text-slate-600">Tanggal paling awal<input type="date" wire:model="fieldValidation.min_date" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label>
                                <label class="block text-[11px] text-slate-600">Tanggal paling akhir<input type="date" wire:model="fieldValidation.max_date" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label>
                            @elseif ($selectedField['type'] === 'checkbox')
                                <div class="grid grid-cols-2 gap-2"><label class="text-[11px] text-slate-600">Jumlah minimum<input type="number" min="0" max="100" wire:model="fieldValidation.min_selections" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label><label class="text-[11px] text-slate-600">Jumlah maksimum<input type="number" min="0" max="100" wire:model="fieldValidation.max_selections" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label></div>
                                <label class="block text-[11px] text-slate-600">Atau jumlah tepat<input type="number" min="0" max="100" wire:model="fieldValidation.exact_selections" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label>
                            @elseif ($selectedField['type'] === 'file')
                                <fieldset><legend class="mb-1 text-[11px] text-slate-600">Jenis file yang diizinkan</legend><div class="grid grid-cols-3 gap-1">@foreach ($validationFileTypes as $extension => $label)<label wire:key="validation-file-type-{{ $selectedField['id'] }}-{{ $extension }}" class="flex items-center gap-1 text-[10px] text-slate-600"><input type="checkbox" value="{{ $extension }}" wire:model="selectedFileTypes" class="rounded border-slate-300">{{ $label }}</label>@endforeach</div><p class="mt-1 text-[10px] text-slate-500">Tanpa pilihan, semua format aman yang didukung diizinkan.</p></fieldset>
                                <label class="block text-[11px] text-slate-600">Maksimum jumlah file<input type="number" min="1" max="5" wire:model="fieldValidation.max_files" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label>
                                <label class="block text-[11px] text-slate-600">Ukuran maksimum per file (KB)<input type="number" min="1" max="10240" wire:model="fieldValidation.max_size_kb" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></label>
                            @else
                                <p class="text-[11px] leading-4 text-slate-500">Tipe field ini memakai validasi bawaan. Format bawaan Smart Fields tidak dapat dilonggarkan.</p>
                            @endif
                            <label class="block text-[11px] text-slate-600">Pesan kesalahan khusus (opsional)<textarea maxlength="240" rows="2" wire:model="fieldValidation.custom_message" class="mt-1 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs" placeholder="Contoh: Periksa kembali jawaban Anda."></textarea></label>
                            @error('pages.*.fields.*.validation')<p class="text-xs text-red-700">{{ $message }}</p>@enderror
                            <button type="button" wire:click="saveFieldValidation" wire:loading.attr="disabled" class="w-full rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700 disabled:opacity-50">Simpan validasi</button>
                        </section>
                        <div class="border-t border-slate-100 pt-3">
                            <button type="button" wire:click="duplicateField('{{ $selectedField['id'] }}')" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Duplikasi field</button>
                            <button type="button" @click="$dispatch('formora-confirm', { message: 'Hapus field ini?', action: () => $wire.deleteField('{{ $selectedField['id'] }}') })" class="mt-2 w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-red-700 hover:bg-red-50">Hapus field</button>
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-xs text-slate-500">Pilih halaman atau field untuk mengubah pengaturannya.</p>
                    <p class="mt-5 text-xs font-medium text-slate-600">{{ count($activePage['fields']) }} field di halaman ini</p>
                @endif
            </aside>
        </main>
    @endif

    <div x-cloak x-show="confirmOpen" x-transition.opacity class="fixed inset-0 z-[70] grid place-items-center bg-slate-950/50 p-4" role="presentation" @click.self="confirmOpen = false">
        <section x-show="confirmOpen" x-transition class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-description">
            <div class="grid size-11 place-items-center rounded-full bg-amber-50 text-xl text-amber-700" aria-hidden="true">!</div>
            <h2 id="confirm-title" class="mt-4 text-lg font-semibold text-slate-950">Konfirmasi tindakan</h2>
            <p id="confirm-description" class="mt-2 text-sm leading-6 text-slate-600" x-text="confirmMessage"></p>
            <div class="mt-6 flex justify-end gap-2"><button type="button" @click="confirmOpen = false; confirmAction = null" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button><button type="button" @click="const action = confirmAction; confirmOpen = false; confirmAction = null; action?.()" class="rounded-xl bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800">Ya, lanjutkan</button></div>
        </section>
    </div>

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
