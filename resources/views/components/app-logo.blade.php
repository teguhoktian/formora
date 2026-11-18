@props([
    'sidebar' => false,
])

<a {{ $attributes->class(['flex min-w-0 items-center gap-2.5', 'w-full' => $sidebar]) }}>
    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-slate-950 text-emerald-300 shadow-sm">
        <x-app-logo-icon class="size-5" />
    </span>
    <span @class([
        'truncate font-bold tracking-tight text-slate-950',
        'text-lg' => $sidebar,
        'text-xl' => ! $sidebar,
    ])>
        Formora<span class="text-emerald-600">.</span>
    </span>
</a>
