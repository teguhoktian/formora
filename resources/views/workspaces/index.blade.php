<x-layouts::app :title="__('Workspace')">
    <div class="min-h-screen bg-[#f7f8fa] px-4 py-7 sm:px-6 lg:px-10 lg:py-9">
        <div class="mx-auto max-w-5xl space-y-6">
            <x-page-header eyebrow="Pengaturan tim" title="Workspace" description="Kelola ruang kerja aktif dan anggota yang dapat mengakses form serta responsnya.">
                <form method="POST" action="{{ route('workspaces.store') }}" class="flex gap-2">@csrf
                    <label for="new-workspace-name" class="sr-only">Nama workspace baru</label>
                    <input id="new-workspace-name" name="name" required maxlength="120" placeholder="Nama workspace baru" class="w-48 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Buat</button>
                </form>
            </x-page-header>
            @if (session('status'))<p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>@endif
            @if ($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="active-workspace-heading">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Workspace aktif</p><h2 id="active-workspace-heading" class="mt-1 text-xl font-semibold text-slate-950">{{ $workspace->name }}</h2><p class="mt-1 text-sm text-slate-500">Peran Anda: {{ ucfirst($membership?->role ?? 'anggota') }}</p></div>
                    @if ($workspaces->count() > 1)
                        <form method="POST" action="{{ route('workspaces.switch') }}" class="flex items-center gap-2">@csrf<label for="workspace-switch" class="sr-only">Pilih workspace</label><select id="workspace-switch" name="workspace_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">@foreach ($workspaces as $option)<option value="{{ $option->id }}" @selected($workspace->id === $option->id)>{{ $option->name }}</option>@endforeach</select><button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Ganti</button></form>
                    @endif
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="members-heading">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 id="members-heading" class="font-semibold text-slate-950">Anggota workspace</h2><p class="mt-1 text-xs text-slate-500">Akses dibatasi pada peran masing-masing anggota.</p></div>
                @if ($canManageMembers)
                    <form method="POST" action="{{ route('workspaces.members.store', $workspace) }}" class="flex flex-col gap-2 border-b border-slate-100 bg-slate-50/70 p-5 sm:flex-row sm:items-end sm:px-6">@csrf
                        <div class="flex-1"><label for="member-email" class="mb-1 block text-xs font-medium text-slate-600">Email pengguna terdaftar</label><input id="member-email" name="email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                        <div><label for="member-role" class="mb-1 block text-xs font-medium text-slate-600">Peran</label><select id="member-role" name="role" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"><option value="member">Member</option><option value="admin">Admin</option><option value="viewer">Viewer</option></select></div>
                        <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Tambah anggota</button>
                    </form>
                @endif
                <ul class="divide-y divide-slate-100">
                    @foreach ($workspace->workspaceMembers as $member)
                        <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $member->user->name }} @if ($member->user_id === auth()->id())<span class="font-normal text-slate-500">(Anda)</span>@endif</p><p class="truncate text-xs text-slate-500">{{ $member->user->email }}</p></div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($canManageMembers && $member->role !== 'owner')
                                    <form method="POST" action="{{ route('workspaces.members.update', [$workspace, $member]) }}">@csrf @method('PATCH')<label for="member-role-{{ $member->id }}" class="sr-only">Peran {{ $member->user->name }}</label><select id="member-role-{{ $member->id }}" name="role" onchange="this.form.requestSubmit()" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs"><option value="admin" @selected($member->role === 'admin')>Admin</option><option value="member" @selected($member->role === 'member')>Member</option><option value="viewer" @selected($member->role === 'viewer')>Viewer</option></select></form>
                                    <form method="POST" action="{{ route('workspaces.members.destroy', [$workspace, $member]) }}" onsubmit="return confirm('Hapus anggota ini dari workspace?')">@csrf @method('DELETE')<button class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Hapus</button></form>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ ucfirst($member->role) }}</span>
                                @endif
                                @if ($canTransferOwnership && $member->role !== 'owner')<form method="POST" action="{{ route('workspaces.ownership.transfer', $workspace) }}" onsubmit="return confirm('Alihkan kepemilikan workspace kepada anggota ini?')">@csrf<input type="hidden" name="member_id" value="{{ $member->id }}"><button class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50">Jadikan owner</button></form>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-layouts::app>
