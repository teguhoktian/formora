<x-layouts::app :title="__('Smart Fields')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-6 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-7xl space-y-7">
            <x-page-header eyebrow="Referensi field" title="Smart Fields Indonesia" description="Field siap pakai dengan format dan validasi yang dirancang untuk kebutuhan formulir di Indonesia.">
                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">{{ count($definitions) }} field tersedia</span>
            </x-page-header>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($definitions as $type => $definition)
                    <article class="flex h-full flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm" aria-labelledby="smart-field-{{ $type }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 id="smart-field-{{ $type }}" class="text-base font-semibold text-slate-950">{{ $definition['label'] }}</h2>
                                <p class="mt-1 text-xs font-medium text-slate-500">{{ $definition['toolbox_label'] }}</p>
                            </div>
                            @if ($definition['sensitive'])
                                <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800">Data sensitif</span>
                            @else
                                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Umum</span>
                            @endif
                        </div>

                        <p class="mt-4 flex-1 text-sm leading-6 text-slate-600">{{ $definition['help_text'] }}</p>

                        <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 text-xs">
                            <div>
                                <dt class="text-slate-500">Jenis input</dt>
                                <dd class="mt-1 font-medium text-slate-800">{{ match ($definition['input_type']) { 'text' => 'Teks', 'date' => 'Tanggal', 'select' => 'Pilihan', 'number' => 'Angka', 'tel' => 'Telepon', 'address' => 'Alamat bertingkat', default => $definition['input_type'] } }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Batas karakter</dt>
                                <dd class="mt-1 font-medium text-slate-800">{{ number_format($definition['max_length'], 0, ',', '.') }}</dd>
                            </div>
                        </dl>

                        @if (isset($definition['options']) && $definition['options'] !== [])
                            <p class="mt-3 text-xs text-slate-500">{{ count($definition['options']) }} pilihan tersedia</p>
                        @endif
                    </article>
                @endforeach
            </div>

            <aside class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-950">
                <h2 class="font-semibold">Catatan penggunaan</h2>
                <p class="mt-1">Validasi format tidak memverifikasi identitas ke Dukcapil, DJP, bank, atau lembaga lain. Field yang ditandai sensitif dimasking pada tampilan respons dan ekspor yang didukung.</p>
            </aside>
        </div>
    </div>
</x-layouts::app>
