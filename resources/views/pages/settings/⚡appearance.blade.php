<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Pengaturan tampilan')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">Pengaturan tampilan</flux:heading>

    <x-pages::settings.layout heading="Tampilan" subheading="Formora saat ini menggunakan tema terang.">
        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <flux:icon.sun class="size-5 text-emerald-700" />
            <span>Tema terang aktif</span>
        </div>
    </x-pages::settings.layout>
</section>
