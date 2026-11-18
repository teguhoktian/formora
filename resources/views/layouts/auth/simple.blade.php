<!DOCTYPE html>
<html lang="id">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f7f8fa] text-slate-900 antialiased">
        <div class="relative isolate flex min-h-svh flex-col items-center justify-center overflow-hidden px-5 py-10 sm:px-8">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_50%_0%,rgba(16,185,129,0.10),transparent_42%)]" aria-hidden="true"></div>
            <x-app-logo href="{{ route('home') }}" class="mb-7" wire:navigate />
            <main class="w-full max-w-md rounded-2xl border border-slate-200/80 bg-white p-6 shadow-[0_18px_60px_-32px_rgba(15,23,42,0.28)] sm:p-9">
                {{ $slot }}
            </main>
            <p class="mt-6 text-center text-xs text-slate-500">Baca <a href="{{ route('privacy') }}" class="font-medium text-emerald-800 underline underline-offset-2">pemberitahuan privasi Formora</a>.</p>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
