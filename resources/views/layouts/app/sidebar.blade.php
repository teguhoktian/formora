<!DOCTYPE html>
<html lang="id">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f7f8fa] text-slate-900 antialiased">
        <flux:sidebar sticky collapsible="mobile" class="formora-scrollbar border-e border-slate-200/80 bg-white shadow-[4px_0_24px_-20px_rgba(15,23,42,0.2)]">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <div class="mx-3 mt-4 rounded-xl border border-slate-200 bg-slate-50/80 p-3">
                <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Workspace aktif</p>
                @php($workspaces = auth()->user()->workspaces()->orderBy('name')->get())
                @php($activeWorkspace = request()->attributes->get('currentWorkspace'))
                @if ($workspaces->isNotEmpty())
                    <form method="POST" action="{{ route('workspaces.switch') }}" class="space-y-2">
                        @csrf
                        <label for="active-workspace" class="sr-only">Pilih workspace</label>
                        <select id="active-workspace" name="workspace_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                            @foreach ($workspaces as $workspaceOption)
                                <option value="{{ $workspaceOption->id }}" @selected($activeWorkspace?->id === $workspaceOption->id)>{{ $workspaceOption->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="mt-1 w-full rounded-lg px-2.5 py-1.5 text-left text-xs font-semibold text-emerald-800 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600">Ganti workspace <span aria-hidden="true">→</span></button>
                    </form>
                @else
                    <p class="text-sm font-medium text-slate-700">Belum ada workspace</p>
                @endif
            </div>

            <flux:sidebar.nav class="mt-5 px-2">
                <flux:sidebar.group heading="Workspace" class="grid">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        Ringkasan
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('forms.index')" :current="request()->routeIs('forms.*')" wire:navigate>
                        Form saya
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="inbox" :href="route('responses.index')" :current="request()->routeIs('responses.*', 'forms.submissions.*')" wire:navigate>
                        Respons
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open-text" :href="route('templates.index')" :current="request()->routeIs('templates.*')" wire:navigate>
                        Template
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office-2" :href="route('workspaces.index')" :current="request()->routeIs('workspaces.*')" wire:navigate>
                        Workspace
                    </flux:sidebar.item>
                </flux:sidebar.group>
                <flux:sidebar.group heading="Fitur" class="mt-5 grid">
                    <flux:sidebar.item icon="rectangle-stack" :href="route('smart-fields.index')" :current="request()->routeIs('smart-fields.*')" wire:navigate>
                        Smart Fields
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('analytics.index')" :current="request()->routeIs('analytics.*')" wire:navigate>
                        Analitik
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav class="px-2">
                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.*', 'security.*', 'appearance.*')" wire:navigate>
                    Pengaturan
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <flux:header class="border-b border-slate-200 bg-white lg:hidden">
            <flux:sidebar.toggle class="mr-2" icon="bars-2" inset="left" />
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            <flux:spacer />
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <flux:menu.item :href="route('profile.edit')" icon="cog-6-tooth" wire:navigate>Pengaturan</flux:menu.item>
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">Keluar</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group><flux:toast /></flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
