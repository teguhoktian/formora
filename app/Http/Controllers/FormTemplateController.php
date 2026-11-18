<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormTemplate;
use App\Models\User;
use App\Models\Workspace;
use App\Services\CurrentWorkspace;
use App\Services\FormSchemaService;
use App\Services\FormTemplateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FormTemplateController extends Controller
{
    public function index(Request $request, CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->get($request->user(), $request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);
        $query = $this->libraryQuery($request->user(), $workspace);
        $categories = (clone $query)->distinct()->orderBy('category')->pluck('category');

        if (isset($filters['q']) && trim($filters['q']) !== '') {
            $search = '%'.mb_strtolower(trim($filters['q'])).'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder->whereRaw('LOWER(title) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(category) LIKE ?', [$search]);
            });
        }
        if (isset($filters['category']) && $filters['category'] !== '') {
            $query->where('category', $filters['category']);
        }

        $templates = $query->with('latestVersion', 'creator')
            ->orderByRaw("CASE WHEN kind = 'official' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->paginate(18)
            ->withQueryString();
        $editableForms = $workspace?->forms()->latest()->get()
            ->filter(fn (Form $form): bool => $request->user()->can('update', $form))
            ->values() ?? collect();

        return view('templates.index', [
            'workspace' => $workspace,
            'templates' => $templates,
            'categories' => $categories,
            'editableForms' => $editableForms,
            'canReview' => $request->user()->can('review', FormTemplate::class),
        ]);
    }

    public function show(string $slug, Request $request, CurrentWorkspace $currentWorkspace): View
    {
        $template = FormTemplate::query()->where('slug', $slug)->with('latestVersion', 'versions', 'creator', 'workspace')->firstOrFail();
        Gate::authorize('view', $template);
        $workspace = $currentWorkspace->get($request->user(), $request);
        $editableForms = $workspace?->forms()->latest()->get()
            ->filter(fn (Form $form): bool => $request->user()->can('update', $form))
            ->values() ?? collect();

        return view('templates.show', [
            'template' => $template,
            'workspace' => $workspace,
            'editableForms' => $editableForms,
            'canUpdate' => $request->user()->can('update', $template),
            'canUse' => $workspace !== null && $request->user()->can('create', [Form::class, $workspace]),
        ]);
    }

    public function saveFromForm(Request $request, CurrentWorkspace $currentWorkspace, FormSchemaService $schemas, FormTemplateService $templates): RedirectResponse
    {
        $workspace = $currentWorkspace->get($request->user(), $request);
        abort_if($workspace === null, 422, 'Pilih workspace terlebih dahulu.');
        Gate::authorize('create', [FormTemplate::class, $workspace->id]);
        $validated = $request->validate([
            'form_id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:80'],
        ]);
        $form = $workspace->forms()->whereKey($validated['form_id'])->firstOrFail();
        Gate::authorize('update', $form);
        $template = $templates->saveFromForm($workspace, $request->user(), $form, [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => trim($validated['category']),
        ], $schemas);

        return to_route('templates.show', $template->slug)->with('status', 'Template tersimpan sebagai privat.');
    }

    public function use(string $slug, Request $request, CurrentWorkspace $currentWorkspace, FormSchemaService $schemas, FormTemplateService $templates): RedirectResponse
    {
        $workspace = $currentWorkspace->get($request->user(), $request);
        abort_if($workspace === null, 422, 'Pilih workspace terlebih dahulu.');
        $template = FormTemplate::query()->where('slug', $slug)->with('latestVersion')->firstOrFail();
        Gate::authorize('view', $template);
        Gate::authorize('create', [Form::class, $workspace]);
        $form = $templates->useTemplate($template, $workspace, $schemas);

        return to_route('forms.edit', $form)->with('status', 'Form baru dibuat dari template.');
    }

    public function update(string $slug, Request $request): RedirectResponse
    {
        $template = FormTemplate::query()->where('slug', $slug)->firstOrFail();
        Gate::authorize('update', $template);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:80'],
            'visibility' => ['required', 'in:private,unlisted,public'],
        ]);
        $isCommunitySubmission = $validated['visibility'] === 'public';
        $template->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => trim($validated['category']),
            'visibility' => $validated['visibility'],
            'status' => $isCommunitySubmission ? 'pending' : 'published',
            'moderated_by' => null,
            'moderation_note' => null,
            'moderated_at' => null,
        ]);

        return to_route('templates.show', $template->slug)->with('status', $isCommunitySubmission
            ? 'Template diajukan untuk moderasi sebelum ditampilkan ke komunitas.'
            : 'Pengaturan template diperbarui.');
    }

    public function createVersion(string $slug, Request $request, CurrentWorkspace $currentWorkspace, FormSchemaService $schemas, FormTemplateService $templates): RedirectResponse
    {
        $template = FormTemplate::query()->where('slug', $slug)->firstOrFail();
        Gate::authorize('update', $template);
        $workspace = $currentWorkspace->get($request->user(), $request);
        abort_if($workspace === null, 422, 'Pilih workspace terlebih dahulu.');
        abort_unless($template->workspace_id === $workspace->id, 404);
        $validated = $request->validate(['form_id' => ['required', 'uuid']]);
        $form = $workspace->forms()->whereKey($validated['form_id'])->firstOrFail();
        Gate::authorize('update', $form);
        $version = $form->versions()->orderByDesc('version_number')->firstOrFail();
        $templateVersion = $templates->createVersion($template, $request->user(), $version->schema, $schemas);

        return to_route('templates.show', $template->slug)->with('status', 'Versi template '.$templateVersion->version_number.' disimpan.');
    }

    public function moderationQueue(Request $request): View
    {
        Gate::authorize('review', FormTemplate::class);
        $templates = FormTemplate::query()
            ->where('kind', 'workspace')
            ->where('visibility', 'public')
            ->where('status', 'pending')
            ->with('latestVersion', 'creator', 'workspace')
            ->orderBy('created_at')
            ->paginate(20);

        return view('templates.moderation', ['templates' => $templates]);
    }

    public function moderate(string $slug, Request $request): RedirectResponse
    {
        Gate::authorize('review', FormTemplate::class);
        $template = FormTemplate::query()->where('slug', $slug)->where('kind', 'workspace')
            ->where('visibility', 'public')->where('status', 'pending')->firstOrFail();
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'moderation_note' => ['nullable', 'required_if:decision,reject', 'string', 'max:2000'],
        ]);
        $template->update([
            'status' => $validated['decision'] === 'approve' ? 'published' : 'rejected',
            'moderation_note' => $validated['moderation_note'] ?? null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return to_route('templates.moderation')->with('status', $validated['decision'] === 'approve'
            ? 'Template komunitas disetujui.'
            : 'Template komunitas ditolak.');
    }

    /** @return Builder<FormTemplate> */
    private function libraryQuery(User $user, ?Workspace $workspace): Builder
    {
        $query = FormTemplate::query()->where(function (Builder $visible) use ($user, $workspace): void {
            $visible->where(function (Builder $official): void {
                $official->where('kind', 'official')->where('status', 'published');
            })->orWhere(function (Builder $community): void {
                $community->where('kind', 'workspace')->where('visibility', 'public')->where('status', 'published');
            });

            if ($workspace !== null) {
                $canManageWorkspaceTemplates = $user->workspaceMemberships()
                    ->where('workspace_id', $workspace->id)->whereIn('role', ['owner', 'admin'])->exists();
                $visible->orWhere(function (Builder $owned) use ($user, $workspace, $canManageWorkspaceTemplates): void {
                    $owned->where('kind', 'workspace')->where('workspace_id', $workspace->id)
                        ->when(! $canManageWorkspaceTemplates, fn (Builder $scope): Builder => $scope->where('created_by', $user->id));
                });
            }
        });

        return $query;
    }
}
