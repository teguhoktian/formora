<x-layouts::app.sidebar :title="$title ?? null">
    <flux:header class="hidden border-b border-slate-200/80 bg-white/90 px-8 py-3 backdrop-blur lg:flex">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span class="font-medium text-slate-400">Workspace</span>
            <span aria-hidden="true">/</span>
            <span class="font-semibold text-slate-800">{{ request()->attributes->get('currentWorkspace')?->name ?? 'Belum ada workspace' }}</span>
        </div>
        <flux:spacer />
        <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
            <span class="size-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
            <span>{{ auth()->user()->name }}</span>
        </div>
    </flux:header>
    <flux:main class="p-0">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
