@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<header class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-2 text-3xl font-semibold tracking-[-0.035em] text-slate-950 sm:text-[2rem]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ $description }}</p>
        @endif
    </div>
    @if (isset($slot) && trim((string) $slot) !== '')
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</header>
