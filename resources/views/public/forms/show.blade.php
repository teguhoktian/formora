<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->title }} · Formora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    @php
        $appearance = $schema['settings']['appearance'] ?? ['accent' => 'emerald', 'width' => 'standard'];
        $publicAccent = match ($appearance['accent'] ?? 'emerald') { 'blue' => ['button' => 'bg-blue-700 hover:bg-blue-800', 'text' => 'text-blue-800', 'soft' => 'bg-blue-50'], 'violet' => ['button' => 'bg-violet-700 hover:bg-violet-800', 'text' => 'text-violet-800', 'soft' => 'bg-violet-50'], default => ['button' => 'bg-emerald-700 hover:bg-emerald-800', 'text' => 'text-emerald-800', 'soft' => 'bg-emerald-50'] };
        $publicWidth = match ($appearance['width'] ?? 'standard') { 'compact' => 'max-w-xl', 'wide' => 'max-w-5xl', default => 'max-w-3xl' };
    @endphp
    <main class="mx-auto {{ $publicWidth }} px-4 py-8 sm:px-6 lg:py-14">
        <header class="mb-7 text-center">
            <a href="{{ route('home') }}" class="text-xs font-bold uppercase tracking-[0.2em] {{ $publicAccent['text'] }}">Formora</a>
        </header>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-7 sm:px-10 sm:py-9">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">{{ $form->title }}</h1>
                @if ($form->description)
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $form->description }}</p>
                @endif
            </div>

            @if ($referenceNumber)
                <div class="px-6 py-12 text-center sm:px-10">
                    <span class="mx-auto grid size-12 place-items-center rounded-full {{ $publicAccent['soft'] }} text-2xl {{ $publicAccent['text'] }}" aria-hidden="true">✓</span>
                    <h2 class="mt-4 text-xl font-semibold text-slate-950">{{ $schema['settings']['success_message'] }}</h2>
                    <p class="mt-3 text-sm text-slate-600">Nomor referensi Anda</p>
                    <p class="mt-1 font-mono text-lg font-semibold tracking-wide {{ $publicAccent['text'] }}">{{ $referenceNumber }}</p>
                    <p class="mt-3 text-xs text-slate-400">Simpan nomor ini untuk referensi Anda.</p>
                </div>
            @else
                @if ($errors->any())
                    <div class="mx-6 mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 sm:mx-10" role="alert">
                        <p class="font-semibold">Periksa isian yang ditandai lalu kirim kembali.</p>
                        @foreach ($errors->all() as $error)
                            <p class="mt-1">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('public.forms.submit', $form->slug) }}" enctype="multipart/form-data" data-public-stepper>
                    @csrf
                    <input type="hidden" name="current_page" value="{{ $failedPage }}" data-current-page>
                    <div class="absolute -left-[10000px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    @foreach ($schema['pages'] as $pageIndex => $page)
                        <section data-public-page="{{ $pageIndex }}" class="px-6 py-7 sm:px-10 sm:py-9" aria-label="{{ $page['title'] }}">
                            <div class="mb-7 flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] {{ $publicAccent['text'] }}">Halaman {{ $pageIndex + 1 }} dari {{ count($schema['pages']) }}</p>
                                    <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ $page['title'] }}</h2>
                                    @if ($page['description'])<p class="mt-2 text-sm leading-6 text-slate-500">{{ $page['description'] }}</p>@endif
                                </div>
                            </div>

                            <div class="space-y-7">
                                @foreach ($page['fields'] as $field)
                                    @php($fieldName = 'answers['.$field['name'].']')
                                    @php($fieldValidation = $field['validation'] ?? [])
                                    <div class="space-y-2" data-field-id="{{ $field['id'] }}" data-field-name="{{ $field['name'] }}" @if (is_array($field['visibility'] ?? null)) data-visibility-source="{{ $field['visibility']['field_id'] }}" data-visibility-value="{{ $field['visibility']['value'] }}" @endif>
                                        <label @if (!in_array($field['type'], ['radio', 'checkbox', 'smart_address'], true) && !($field['type'] === 'date' && ($field['settings']['mode'] ?? 'date') === 'date_range')) for="field-{{ $field['id'] }}" @endif class="block text-sm font-semibold text-slate-800">
                                            {{ $field['label'] }}@if ($field['required']) <span class="text-red-600" aria-label="wajib diisi">*</span>@endif
                                        </label>
                                        @if ($field['type'] === 'date' && ($field['settings']['mode'] ?? 'date') === 'date_range')
                                            <div class="grid gap-3 sm:grid-cols-2"><label class="text-xs font-medium text-slate-600">Dari<input id="field-{{ $field['id'] }}-start" type="date" name="{{ $fieldName }}[start]" @required($field['required']) @if (isset($fieldValidation['min_date'])) min="{{ $fieldValidation['min_date'] }}" @endif @if (isset($fieldValidation['max_date'])) max="{{ $fieldValidation['max_date'] }}" @endif class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm"></label><label class="text-xs font-medium text-slate-600">Sampai<input id="field-{{ $field['id'] }}-end" type="date" name="{{ $fieldName }}[end]" @required($field['required']) @if (isset($fieldValidation['min_date'])) min="{{ $fieldValidation['min_date'] }}" @endif @if (isset($fieldValidation['max_date'])) max="{{ $fieldValidation['max_date'] }}" @endif class="mt-1 w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm"></label></div>
                                        @elseif ($field['type'] === 'rating')
                                            <fieldset id="field-{{ $field['id'] }}" data-rating class="flex flex-wrap gap-1" aria-label="Pilih rating dari 1 sampai {{ $field['settings']['scale'] }} bintang">@for ($rating = 1; $rating <= $field['settings']['scale']; $rating++)<label data-rating-choice="{{ $rating }}" class="cursor-pointer rounded-lg p-1 text-3xl leading-none text-slate-300 transition hover:text-amber-400"><input class="sr-only" type="radio" name="{{ $fieldName }}" value="{{ $rating }}" @required($field['required']) aria-label="{{ $rating }} dari {{ $field['settings']['scale'] }} bintang"><span aria-hidden="true">★</span></label>@endfor</fieldset>
                                        @elseif ($field['type'] === 'slider')
                                            <div class="space-y-2"><input id="field-{{ $field['id'] }}" data-number-slider type="range" name="{{ $fieldName }}" min="{{ $field['settings']['min'] }}" max="{{ $field['settings']['max'] }}" step="{{ $field['settings']['step'] }}" value="{{ $field['settings']['min'] }}" @required($field['required']) class="w-full accent-emerald-700"><div class="flex justify-between text-xs text-slate-500"><span>{{ $field['settings']['min'] }}</span><output data-slider-value for="field-{{ $field['id'] }}" class="font-semibold text-slate-800">{{ $field['settings']['min'] }}</output><span>{{ $field['settings']['max'] }}</span></div></div>
                                        @elseif ($field['type'] === 'date' && ($field['settings']['mode'] ?? 'date') !== 'date')
                                            @php($dateMode = $field['settings']['mode'])
                                            <input id="field-{{ $field['id'] }}" type="{{ $dateMode === 'time' ? 'time' : ($dateMode === 'datetime' ? 'datetime-local' : 'date') }}" name="{{ $fieldName }}" @required($field['required']) class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                        @elseif ($field['type'] === 'textarea')
                                            <textarea id="field-{{ $field['id'] }}" name="{{ $fieldName }}" rows="4" maxlength="{{ $fieldValidation['max_length'] ?? 20000 }}" @if (isset($fieldValidation['min_length'])) minlength="{{ $fieldValidation['min_length'] }}" @endif class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"></textarea>
                                        @elseif ($field['type'] === 'select')
                                            <select id="field-{{ $field['id'] }}" name="{{ $fieldName }}" data-searchable-select class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                                <option value="">Pilih jawaban</option>
                                                @foreach ($field['settings']['options'] as $option)<option value="{{ $option['value'] }}">{{ $option['label'] }}</option>@endforeach
                                            </select>
                                        @elseif (in_array($field['type'], ['radio', 'checkbox'], true))
                                            <div class="space-y-2.5">
                                                @foreach ($field['settings']['options'] as $option)
                                                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-3.5 py-3 text-sm text-slate-700 hover:bg-slate-50">
                                                        <input type="{{ $field['type'] }}" name="{{ $fieldName }}{{ $field['type'] === 'checkbox' ? '[]' : '' }}" value="{{ $option['value'] }}" class="size-4 border-slate-300 text-emerald-700 focus:ring-emerald-600">
                                                        <span>{{ $option['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @elseif ($field['type'] === 'file')
                                            @php($allowedFileTypes = $fieldValidation['allowed_types'] ?? ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'])
                                            @php($maxFiles = $fieldValidation['max_files'] ?? 1)
                                            <div data-file-manager data-max-files="{{ $maxFiles }}" data-max-size-kb="{{ $fieldValidation['max_size_kb'] ?? 10240 }}" class="space-y-3">
                                                <input id="field-{{ $field['id'] }}" type="file" name="{{ $fieldName }}{{ $maxFiles > 1 ? '[]' : '' }}" @if ($maxFiles > 1) multiple @endif @required($field['required']) accept="{{ implode(',', array_map(fn ($extension) => '.'.$extension, $allowedFileTypes)) }}" class="sr-only" data-file-input>
                                                <label for="field-{{ $field['id'] }}" data-file-dropzone tabindex="0" class="flex cursor-pointer flex-col items-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center transition hover:border-emerald-400 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600"><span class="grid size-12 place-items-center rounded-xl bg-white text-2xl text-emerald-700 shadow-sm" aria-hidden="true">⇧</span><span class="mt-3 text-sm font-semibold text-slate-800">Tarik file ke sini atau <span class="text-emerald-700">pilih file</span></span><span class="mt-1 text-xs text-slate-500">{{ strtoupper(implode(', ', $allowedFileTypes)) }} · maksimal {{ $maxFiles }} file · {{ number_format(($fieldValidation['max_size_kb'] ?? 10240) / 1024, 1) }} MB per file</span></label>
                                                <p data-file-error aria-live="polite" class="hidden text-xs font-medium text-red-700"></p><ul data-file-list class="grid gap-2 sm:grid-cols-2"></ul>
                                            </div>
                                        @elseif ($field['type'] === 'smart_address')
                                            <div class="grid gap-3 sm:grid-cols-2" data-smart-address data-regions-url="{{ url('/smart-fields/regions') }}">
                                                <div>
                                                    <label for="field-{{ $field['id'] }}-province" class="mb-1 block text-xs font-medium text-slate-600">Provinsi @if ($field['required']) <span class="text-red-600">*</span>@endif</label>
                                                    <select id="field-{{ $field['id'] }}-province" name="{{ $fieldName }}[province]" data-address-region="province" @required($field['required']) class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                                        <option value="">Pilih provinsi</option>
                                                        @foreach ($provinces as $province)<option value="{{ $province['code'] }}">{{ $province['name'] }}</option>@endforeach
                                                    </select>
                                                    @error('answers.'.$field['name'].'.province')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                                                </div>
                                                <div>
                                                    <label for="field-{{ $field['id'] }}-regency" class="mb-1 block text-xs font-medium text-slate-600">Kabupaten/Kota @if ($field['required']) <span class="text-red-600">*</span>@endif</label>
                                                    <select id="field-{{ $field['id'] }}-regency" name="{{ $fieldName }}[regency]" data-address-region="regency" disabled @required($field['required']) class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 disabled:bg-slate-100">
                                                        <option value="">Pilih provinsi lebih dahulu</option>
                                                    </select>
                                                    @error('answers.'.$field['name'].'.regency')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                                                </div>
                                                <div>
                                                    <label for="field-{{ $field['id'] }}-district" class="mb-1 block text-xs font-medium text-slate-600">Kecamatan @if ($field['required']) <span class="text-red-600">*</span>@endif</label>
                                                    <select id="field-{{ $field['id'] }}-district" name="{{ $fieldName }}[district]" data-address-region="district" disabled @required($field['required']) class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 disabled:bg-slate-100">
                                                        <option value="">Pilih kabupaten/kota lebih dahulu</option>
                                                    </select>
                                                    @error('answers.'.$field['name'].'.district')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                                                </div>
                                                <div>
                                                    <label for="field-{{ $field['id'] }}-village" class="mb-1 block text-xs font-medium text-slate-600">Desa/Kelurahan @if ($field['required']) <span class="text-red-600">*</span>@endif</label>
                                                    <select id="field-{{ $field['id'] }}-village" name="{{ $fieldName }}[village]" data-address-region="village" disabled @required($field['required']) class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 disabled:bg-slate-100">
                                                        <option value="">Pilih kecamatan lebih dahulu</option>
                                                    </select>
                                                    @error('answers.'.$field['name'].'.village')<p class="mt-1 text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                                                </div>
                                            </div>
                                        @elseif (isset($smartFieldDefinitions[$field['type']]))
                                            @php($smartDefinition = $smartFieldDefinitions[$field['type']])
                                            @if ($smartDefinition['input_type'] === 'select')
                                            <select id="field-{{ $field['id'] }}" name="{{ $fieldName }}" data-searchable-select @required($field['required']) class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                                    <option value="">{{ $smartDefinition['placeholder'] }}</option>
                                                    @foreach ($smartDefinition['options'] ?? [] as $bankName)
                                                        <option value="{{ $bankName }}">{{ $bankName }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input id="field-{{ $field['id'] }}" type="{{ $smartDefinition['input_type'] }}" inputmode="{{ $smartDefinition['inputmode'] }}" name="{{ $fieldName }}" placeholder="{{ $smartDefinition['placeholder'] }}" maxlength="{{ $smartDefinition['max_length'] }}" @required($field['required']) @if ($smartDefinition['numeric_only']) data-digits-only pattern="[0-9]*" @endif @if ($field['type'] === 'smart_rupiah') min="0" step="any" @endif autocomplete="off" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                            @endif
                                            @if ($smartDefinition['help_text'])<p class="text-xs leading-5 text-slate-500">{{ $smartDefinition['help_text'] }}</p>@endif
                                        @else
                                                <input id="field-{{ $field['id'] }}" type="{{ match ($field['type']) { 'email' => 'email', 'phone' => 'tel', 'number' => 'number', 'date' => 'date', default => 'text' } }}" name="{{ $fieldName }}" maxlength="{{ $fieldValidation['max_length'] ?? ($field['type'] === 'email' ? 254 : 5000) }}" @if (isset($fieldValidation['min_length'])) minlength="{{ $fieldValidation['min_length'] }}" @endif @if ($field['type'] === 'number' && ($fieldValidation['operator'] ?? '') === 'between') min="{{ $fieldValidation['min'] }}" max="{{ $fieldValidation['max'] }}" @elseif ($field['type'] === 'number' && in_array($fieldValidation['operator'] ?? '', ['greater_than', 'at_least'], true)) min="{{ $fieldValidation['value'] }}" @elseif ($field['type'] === 'number' && in_array($fieldValidation['operator'] ?? '', ['less_than', 'at_most'], true)) max="{{ $fieldValidation['value'] }}" @elseif ($field['type'] === 'date') @if (isset($fieldValidation['min_date'])) min="{{ $fieldValidation['min_date'] }}" @endif @if (isset($fieldValidation['max_date'])) max="{{ $fieldValidation['max_date'] }}" @endif @endif class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                        @endif
                                        @if ($field['help_text'])<p class="text-xs leading-5 text-slate-500">{{ $field['help_text'] }}</p>@endif
                                        @error('answers.'.$field['name'])<p class="text-xs font-medium text-red-700">{{ $message }}</p>@enderror
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-9 flex items-center justify-between gap-3 border-t border-slate-100 pt-5">
                                <button type="button" data-page-back @if ($pageIndex === 0) hidden @endif class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</button>
                                <span @if ($pageIndex === 0) data-first-spacer @endif></span>
                                @if ($pageIndex < count($schema['pages']) - 1)
                                    <button type="button" data-page-next class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Lanjut</button>
                                @else
                                    <button type="submit" class="rounded-xl {{ $publicAccent['button'] }} px-5 py-2.5 text-sm font-semibold text-white">{{ $schema['settings']['submit_label'] }}</button>
                                @endif
                            </div>
                        </section>
                    @endforeach
                </form>
            @endif
        </section>
        <p class="mt-5 text-center text-xs text-slate-500">Baca <a href="{{ route('privacy') }}" class="underline underline-offset-2">pemberitahuan privasi Formora</a>. Untuk tujuan dan penggunaan jawaban, hubungi pengelola formulir.</p>
    </main>

    <script>
        document.querySelectorAll('[data-searchable-select]').forEach((select) => {
            const fieldId = select.id;
            const wrapper = document.createElement('div');
            wrapper.className = 'relative';
            select.parentNode.insertBefore(wrapper, select);
            wrapper.append(select);
            select.classList.add('sr-only');
            select.tabIndex = -1;
            select.setAttribute('aria-hidden', 'true');
            select.id = `${fieldId}-native`;

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.id = fieldId;
            trigger.className = 'flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-left text-sm text-slate-700 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20';
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.innerHTML = '<span data-select-value></span><span aria-hidden="true" class="ml-3 text-slate-400">⌄</span>';

            const dropdown = document.createElement('div');
            dropdown.className = 'absolute z-30 mt-1 w-full rounded-xl border border-slate-200 bg-white p-2 shadow-xl';
            dropdown.hidden = true;
            const search = document.createElement('input');
            search.type = 'search';
            search.placeholder = 'Cari pilihan…';
            search.setAttribute('aria-label', `Cari pilihan untuk ${select.id}`);
            search.className = 'mb-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20';
            const list = document.createElement('div');
            list.className = 'max-h-56 space-y-0.5 overflow-y-auto';
            list.setAttribute('role', 'listbox');
            dropdown.append(search, list);
            wrapper.append(trigger, dropdown);

            const selectedLabel = () => select.selectedOptions[0]?.textContent?.trim() || 'Pilih jawaban';
            const renderOptions = () => {
                const query = search.value.trim().toLocaleLowerCase('id');
                list.replaceChildren();
                Array.from(select.options).filter((option) => option.value !== '' && option.textContent.toLocaleLowerCase('id').includes(query)).forEach((option) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.role = 'option';
                    item.className = `w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-emerald-50 ${option.selected ? 'bg-emerald-50 font-semibold text-emerald-800' : 'text-slate-700'}`;
                    item.textContent = option.textContent;
                    item.setAttribute('aria-selected', String(option.selected));
                    item.addEventListener('click', () => {
                        select.value = option.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        dropdown.hidden = true;
                        trigger.setAttribute('aria-expanded', 'false');
                        trigger.querySelector('[data-select-value]').textContent = selectedLabel();
                        trigger.focus();
                    });
                    list.append(item);
                });
                if (!list.childElementCount) {
                    const empty = document.createElement('p');
                    empty.className = 'px-3 py-2 text-sm text-slate-500';
                    empty.textContent = 'Pilihan tidak ditemukan';
                    list.append(empty);
                }
            };
            trigger.querySelector('[data-select-value]').textContent = selectedLabel();
            renderOptions();
            trigger.addEventListener('click', () => {
                dropdown.hidden = !dropdown.hidden;
                trigger.setAttribute('aria-expanded', String(!dropdown.hidden));
                if (!dropdown.hidden) { search.value = ''; renderOptions(); search.focus(); }
            });
            search.addEventListener('input', renderOptions);
            search.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') { dropdown.hidden = true; trigger.setAttribute('aria-expanded', 'false'); trigger.focus(); }
            });
            document.addEventListener('click', (event) => {
                if (!wrapper.contains(event.target)) { dropdown.hidden = true; trigger.setAttribute('aria-expanded', 'false'); }
            });
            select.addEventListener('change', () => {
                trigger.querySelector('[data-select-value]').textContent = selectedLabel();
                renderOptions();
            });
        });

        document.querySelectorAll('[data-rating]').forEach((rating) => {
            rating.addEventListener('change', (event) => {
                const chosen = Number(event.target.value);
                rating.querySelectorAll('[data-rating-choice]').forEach((choice) => {
                    choice.classList.toggle('text-amber-400', Number(choice.dataset.ratingChoice) <= chosen);
                    choice.classList.toggle('text-slate-300', Number(choice.dataset.ratingChoice) > chosen);
                });
            });
        });

        document.querySelectorAll('[data-number-slider]').forEach((slider) => {
            const output = slider.closest('div')?.querySelector('[data-slider-value]');
            slider.addEventListener('input', () => { if (output) output.value = slider.value; });
        });

        document.querySelectorAll('[data-file-manager]').forEach((manager) => {
            const input = manager.querySelector('[data-file-input]');
            const dropzone = manager.querySelector('[data-file-dropzone]');
            const list = manager.querySelector('[data-file-list]');
            const error = manager.querySelector('[data-file-error]');
            const maxFiles = Number(manager.dataset.maxFiles) || 1;
            const maxSize = (Number(manager.dataset.maxSizeKb) || 10240) * 1024;
            const acceptedExtensions = (input.accept || '').split(',').map((value) => value.trim().replace(/^\./, '').toLowerCase()).filter(Boolean);
            let objectUrls = [];
            const render = () => {
                objectUrls.forEach((url) => URL.revokeObjectURL(url));
                objectUrls = [];
                list.replaceChildren();
                Array.from(input.files || []).forEach((file, index) => {
                    const item = document.createElement('li');
                    item.className = 'flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-white p-2.5';
                    if (file.type.startsWith('image/')) {
                        const image = document.createElement('img');
                        image.src = URL.createObjectURL(file);
                        objectUrls.push(image.src);
                        image.alt = '';
                        image.className = 'size-12 rounded-lg object-cover';
                        item.append(image);
                    } else {
                        const icon = document.createElement('span');
                        icon.className = 'grid size-12 shrink-0 place-items-center rounded-lg bg-slate-100 text-[10px] font-bold uppercase text-slate-600';
                        icon.textContent = file.name.split('.').pop() || 'FILE';
                        item.append(icon);
                    }
                    const details = document.createElement('span');
                    details.className = 'min-w-0 flex-1';
                    const fileName = document.createElement('span');
                    fileName.className = 'block truncate text-xs font-semibold text-slate-800';
                    fileName.textContent = file.name;
                    const fileSize = document.createElement('span');
                    fileSize.className = 'text-[10px] text-slate-500';
                    fileSize.textContent = `${(file.size / 1024).toFixed(0)} KB`;
                    details.append(fileName, fileSize);
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'rounded-lg px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50';
                    remove.textContent = 'Hapus';
                    remove.setAttribute('aria-label', `Hapus ${file.name}`);
                    remove.addEventListener('click', () => {
                        const transfer = new DataTransfer();
                        Array.from(input.files).forEach((existing, fileIndex) => { if (fileIndex !== index) transfer.items.add(existing); });
                        input.files = transfer.files;
                        error.classList.add('hidden');
                        render();
                    });
                    item.append(details, remove);
                    list.append(item);
                });
            };
            const setFiles = (files) => {
                const transfer = new DataTransfer();
                const rejected = [];
                Array.from(files).forEach((file) => {
                    const extension = file.name.split('.').pop().toLowerCase();
                    if (transfer.files.length >= maxFiles) rejected.push(`Maksimal ${maxFiles} file.`);
                    else if (file.size > maxSize) rejected.push(`${file.name} melebihi ukuran maksimum.`);
                    else if (acceptedExtensions.length && !acceptedExtensions.includes(extension)) rejected.push(`Jenis ${extension} tidak diizinkan.`);
                    else transfer.items.add(file);
                });
                input.files = transfer.files;
                error.textContent = [...new Set(rejected)].join(' ');
                error.classList.toggle('hidden', rejected.length === 0);
                render();
            };
            input.addEventListener('change', () => setFiles(input.files));
            dropzone.addEventListener('dragover', (event) => { event.preventDefault(); dropzone.classList.add('border-emerald-500', 'bg-emerald-50'); });
            dropzone.addEventListener('dragleave', () => dropzone.classList.remove('border-emerald-500', 'bg-emerald-50'));
            dropzone.addEventListener('drop', (event) => { event.preventDefault(); dropzone.classList.remove('border-emerald-500', 'bg-emerald-50'); setFiles(event.dataTransfer.files); });
        });

        document.querySelectorAll('[data-public-stepper]').forEach((form) => {
            const pages = Array.from(form.querySelectorAll('[data-public-page]'));
            const pageInput = form.querySelector('[data-current-page]');
            let current = Math.min(Number(pageInput.value) || 0, pages.length - 1);
            const fields = Array.from(form.querySelectorAll('[data-field-id]'));
            const readValue = (fieldName) => {
                const namedControls = Array.from(form.elements).filter((control) => control.name === `answers[${fieldName}]`);
                const selectedRadio = namedControls.find((control) => control.type === 'radio' && control.checked);
                const control = namedControls.find((item) => item.tagName === 'SELECT') ?? selectedRadio ?? namedControls.find((item) => item.type !== 'radio');
                return control?.value ?? '';
            };
            const updateVisibility = () => {
                fields.forEach((field) => {
                    const sourceId = field.dataset.visibilitySource;
                    if (!sourceId) return;
                    const source = fields.find((candidate) => candidate.dataset.fieldId === sourceId);
                    const visible = source && readValue(source.dataset.fieldName) === field.dataset.visibilityValue;
                    const isVisible = Boolean(visible);
                    const wasVisible = field.dataset.visibilityWasVisible === 'true';
                    const controls = Array.from(field.querySelectorAll('input, select, textarea'));

                    controls.forEach((control) => {
                        if (control.dataset.visibilityInitialized !== 'true') {
                            control.dataset.visibilityInitialized = 'true';
                            control.dataset.visibilityOriginalDisabled = String(control.disabled);
                        }
                    });

                    if (!isVisible && wasVisible) {
                        controls.forEach((control) => {
                            if (control.type === 'checkbox' || control.type === 'radio') control.checked = false;
                            else if (control.type === 'file') { control.value = ''; control.dispatchEvent(new Event('change', { bubbles: true })); }
                            else control.value = '';
                        });
                    }
                    field.hidden = !isVisible;
                    field.setAttribute('aria-hidden', String(!isVisible));
                    controls.forEach((control) => { control.disabled = !isVisible || control.dataset.visibilityOriginalDisabled === 'true'; });
                    field.dataset.visibilityWasVisible = String(isVisible);
                });
            };
            const showPage = (index) => {
                current = Math.max(0, Math.min(index, pages.length - 1));
                pages.forEach((page, pageIndex) => { page.hidden = pageIndex !== current; });
                pageInput.value = String(current);
            };
            form.addEventListener('click', (event) => {
                if (event.target.closest('[data-page-next]')) showPage(current + 1);
                if (event.target.closest('[data-page-back]')) showPage(current - 1);
            });
            form.addEventListener('change', updateVisibility);
            form.addEventListener('input', updateVisibility);
            updateVisibility();
            showPage(current);
        });
    </script>
    @livewireScripts
</body>
</html>
