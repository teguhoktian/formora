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
    <main class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:py-14">
        <header class="mb-7 text-center">
            <a href="{{ route('home') }}" class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Formora</a>
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
                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-emerald-50 text-2xl text-emerald-700" aria-hidden="true">✓</span>
                    <h2 class="mt-4 text-xl font-semibold text-slate-950">{{ $schema['settings']['success_message'] }}</h2>
                    <p class="mt-3 text-sm text-slate-600">Nomor referensi Anda</p>
                    <p class="mt-1 font-mono text-lg font-semibold tracking-wide text-emerald-800">{{ $referenceNumber }}</p>
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
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Halaman {{ $pageIndex + 1 }} dari {{ count($schema['pages']) }}</p>
                                    <h2 class="mt-2 text-xl font-semibold text-slate-950">{{ $page['title'] }}</h2>
                                    @if ($page['description'])<p class="mt-2 text-sm leading-6 text-slate-500">{{ $page['description'] }}</p>@endif
                                </div>
                            </div>

                            <div class="space-y-7">
                                @foreach ($page['fields'] as $field)
                                    @php($fieldName = 'answers['.$field['name'].']')
                                    <div class="space-y-2">
                                        <label @if (!in_array($field['type'], ['radio', 'checkbox', 'smart_address'], true)) for="field-{{ $field['id'] }}" @endif class="block text-sm font-semibold text-slate-800">
                                            {{ $field['label'] }}@if ($field['required']) <span class="text-red-600" aria-label="wajib diisi">*</span>@endif
                                        </label>
                                        @if ($field['type'] === 'textarea')
                                            <textarea id="field-{{ $field['id'] }}" name="{{ $fieldName }}" rows="4" maxlength="20000" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"></textarea>
                                        @elseif ($field['type'] === 'select')
                                            <select id="field-{{ $field['id'] }}" name="{{ $fieldName }}" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
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
                                            <input id="field-{{ $field['id'] }}" type="file" name="{{ $fieldName }}" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="block w-full rounded-xl border border-slate-300 text-sm text-slate-600 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:text-sm file:font-semibold file:text-slate-700">
                                            <p class="text-xs text-slate-500">PDF, JPG, PNG, DOC, atau DOCX · maksimal 10 MB</p>
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
                                            <input id="field-{{ $field['id'] }}" type="{{ $smartDefinition['input_type'] }}" inputmode="{{ $smartDefinition['inputmode'] }}" name="{{ $fieldName }}" placeholder="{{ $smartDefinition['placeholder'] }}" maxlength="{{ $smartDefinition['max_length'] }}" @required($field['required']) @if ($smartDefinition['numeric_only']) data-digits-only pattern="[0-9]*" @endif @if ($field['type'] === 'smart_rupiah') min="0" step="any" @endif autocomplete="off" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                                            @if ($smartDefinition['help_text'])<p class="text-xs leading-5 text-slate-500">{{ $smartDefinition['help_text'] }}</p>@endif
                                        @else
                                            <input id="field-{{ $field['id'] }}" type="{{ match ($field['type']) { 'email' => 'email', 'phone' => 'tel', 'number' => 'number', 'date' => 'date', default => 'text' } }}" name="{{ $fieldName }}" maxlength="{{ $field['type'] === 'email' ? 254 : 5000 }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
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
                                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">{{ $schema['settings']['submit_label'] }}</button>
                                @endif
                            </div>
                        </section>
                    @endforeach
                </form>
            @endif
        </section>
        <p class="mt-5 text-center text-xs text-slate-400">Jawaban Anda dikirim dengan aman melalui Formora.</p>
    </main>

    <script>
        document.querySelectorAll('[data-public-stepper]').forEach((form) => {
            const pages = Array.from(form.querySelectorAll('[data-public-page]'));
            const pageInput = form.querySelector('[data-current-page]');
            let current = Math.min(Number(pageInput.value) || 0, pages.length - 1);
            const showPage = (index) => {
                current = Math.max(0, Math.min(index, pages.length - 1));
                pages.forEach((page, pageIndex) => { page.hidden = pageIndex !== current; });
                pageInput.value = String(current);
            };
            form.addEventListener('click', (event) => {
                if (event.target.closest('[data-page-next]')) showPage(current + 1);
                if (event.target.closest('[data-page-back]')) showPage(current - 1);
            });
            showPage(current);
        });
    </script>
    @livewireScripts
</body>
</html>
