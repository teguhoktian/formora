<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\CurrentWorkspace;
use App\Services\WorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(Request $request, CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->get($request->user(), $request);
        abort_if($workspace === null, 404);
        Gate::authorize('view', $workspace);

        $workspace->load(['workspaceMembers.user:id,name,email']);

        return view('workspaces.index', [
            'workspace' => $workspace,
            'membership' => $workspace->workspaceMembers->firstWhere('user_id', $request->user()->id),
            'workspaces' => $request->user()->workspaces()->orderBy('name')->get(['workspaces.id', 'workspaces.name']),
            'canManageMembers' => $request->user()->can('manageMembers', $workspace),
            'canTransferOwnership' => $request->user()->can('transferOwnership', $workspace),
        ]);
    }

    public function store(Request $request, WorkspaceService $workspaces): RedirectResponse
    {
        Gate::authorize('create', Workspace::class);
        $validated = $request->validate(['name' => ['required', 'string', 'max:120']]);

        $workspace = $workspaces->createWorkspace($request->user(), $validated['name']);
        $request->session()->put('active_workspace_id', $workspace->id);

        return to_route('dashboard');
    }

    public function switch(Request $request, WorkspaceService $workspaces): RedirectResponse
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'uuid', Rule::exists('workspace_members', 'workspace_id')
                ->where('user_id', $request->user()->id)],
        ]);

        $membership = $workspaces->membershipFor($request->user(), $validated['workspace_id']);
        $request->session()->put('active_workspace_id', $membership->workspace_id);

        return back();
    }

    public function storeMember(Request $request, Workspace $workspace): RedirectResponse
    {
        Gate::authorize('manageMembers', $workspace);
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'member', 'viewer'])],
        ]);

        $user = User::query()->where('email', $validated['email'])->firstOrFail();
        if ($workspace->workspaceMembers()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'Pengguna sudah menjadi anggota workspace ini.']);
        }

        $workspace->workspaceMembers()->create([
            'user_id' => $user->id,
            'role' => $validated['role'],
        ]);

        return back();
    }

    public function updateMember(Request $request, Workspace $workspace, WorkspaceMember $member): RedirectResponse
    {
        abort_unless($member->workspace_id === $workspace->id, 404);
        Gate::authorize('manage', $member);
        abort_if($member->role === 'owner', 403);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'member', 'viewer'])],
        ]);
        $member->update(['role' => $validated['role']]);

        return back();
    }

    public function destroyMember(Request $request, Workspace $workspace, WorkspaceMember $member, WorkspaceService $workspaces): RedirectResponse
    {
        abort_unless($member->workspace_id === $workspace->id, 404);
        $workspaces->removeMember($request->user(), $member);

        return back();
    }

    public function transferOwnership(Request $request, Workspace $workspace, WorkspaceService $workspaces): RedirectResponse
    {
        Gate::authorize('transferOwnership', $workspace);
        $validated = $request->validate([
            'member_id' => ['required', 'integer', Rule::exists('workspace_members', 'id')->where('workspace_id', $workspace->id)],
        ]);

        $member = $workspace->workspaceMembers()->whereKey($validated['member_id'])->firstOrFail();
        $workspaces->transferOwnership($request->user(), $workspace, $member);

        return back();
    }
}
