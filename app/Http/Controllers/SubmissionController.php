<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateSubmissionExport;
use App\Models\AuditLog;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\SubmissionExport;
use App\Services\CurrentWorkspace;
use App\Services\SubmissionResponsePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function workspaceIndex(Request $request, CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->get($request->user(), $request);
        abort_if($workspace === null, 404);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['new', 'in_review', 'approved', 'rejected', 'completed'])],
        ]);

        $submissions = FormSubmission::query()
            ->select(['id', 'workspace_id', 'form_id', 'form_version_id', 'status', 'reference_number', 'created_at'])
            ->with('form:id,title')
            ->where('workspace_id', $workspace->id)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('responses.index', compact('workspace', 'submissions', 'filters'));
    }

    public function index(Request $request, Form $form, CurrentWorkspace $currentWorkspace): View
    {
        $form = $this->authorizedForm($request, $form, $currentWorkspace);
        Gate::authorize('viewSubmissions', $form);
        $filters = $this->filters($request);
        $submissions = $form->submissions()
            ->select(['id', 'workspace_id', 'form_id', 'form_version_id', 'status', 'reference_number', 'created_at'])
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'], fn ($query, $search) => $query->where(function ($query) use ($search): void {
                $query->whereRaw('CAST(response_data AS TEXT) LIKE ?', ['%'.$search.'%'])
                    ->orWhere('reference_number', 'like', '%'.$search.'%');
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('submissions.index', [
            'form' => $form,
            'submissions' => $submissions,
            'filters' => $filters,
            'canDownload' => $request->user()->can('downloadSubmissions', $form),
            'exports' => $request->user()->can('downloadSubmissions', $form)
                ? SubmissionExport::query()->where('form_id', $form->id)->where('requested_by', $request->user()->id)->latest()->limit(5)->get(['id', 'form_id', 'status', 'created_at'])
                : collect(),
        ]);
    }

    public function show(Request $request, Form $form, FormSubmission $submission, CurrentWorkspace $currentWorkspace, SubmissionResponsePresenter $presenter): View
    {
        $form = $this->authorizedForm($request, $form, $currentWorkspace);
        Gate::authorize('viewSubmissions', $form);
        $submission = $form->submissions()->with('formVersion:id,schema')->whereKey($submission->id)->firstOrFail();

        return view('submissions.show', ['form' => $form, 'submission' => $submission, 'answers' => $presenter->present($submission)]);
    }

    public function update(Request $request, Form $form, FormSubmission $submission, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $form = $this->authorizedForm($request, $form, $currentWorkspace);
        Gate::authorize('update', $form);
        $submission = $form->submissions()->whereKey($submission->id)->firstOrFail();
        $validated = $request->validate([
            'status' => ['required', Rule::in(['new', 'in_review', 'approved', 'rejected', 'completed'])],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $submission->update($validated);
        AuditLog::query()->create([
            'workspace_id' => $form->workspace_id,
            'actor_id' => $request->user()->id,
            'event' => 'submission.updated',
            'subject_type' => FormSubmission::class,
            'subject_id' => $submission->id,
        ]);

        return back()->with('status', 'Respons diperbarui.');
    }

    public function export(Request $request, Form $form, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $form = $this->authorizedForm($request, $form, $currentWorkspace);
        Gate::authorize('downloadSubmissions', $form);
        $filters = $this->filters($request);
        $export = SubmissionExport::query()->create([
            'workspace_id' => $form->workspace_id,
            'form_id' => $form->id,
            'requested_by' => $request->user()->id,
            'status' => 'queued',
            'filters' => $filters,
        ]);
        GenerateSubmissionExport::dispatch($export->id);
        AuditLog::query()->create([
            'workspace_id' => $form->workspace_id,
            'actor_id' => $request->user()->id,
            'event' => 'submission.export_requested',
            'subject_type' => Form::class,
            'subject_id' => $form->id,
        ]);

        return to_route('forms.submissions.index', [$form, ...$filters])->with('status', 'Ekspor CSV masuk antrean.');
    }

    public function download(Request $request, Form $form, SubmissionExport $export, CurrentWorkspace $currentWorkspace): StreamedResponse
    {
        $form = $this->authorizedForm($request, $form, $currentWorkspace);
        Gate::authorize('downloadSubmissions', $form);
        abort_unless($export->form_id === $form->id && $export->workspace_id === $form->workspace_id, 404);
        abort_unless($export->status === 'ready' && $export->file_path !== null, 409, 'Ekspor belum siap.');
        abort_unless(Storage::disk('local')->exists($export->file_path), 404);

        return Storage::disk('local')->download($export->file_path, 'respons-'.$form->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{status: string|null, search: string|null, from: string|null, to: string|null} */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['new', 'in_review', 'approved', 'rejected', 'completed'])],
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return [
            'status' => $validated['status'] ?? null,
            'search' => $validated['search'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }

    private function authorizedForm(Request $request, Form $form, CurrentWorkspace $currentWorkspace): Form
    {
        $workspace = $currentWorkspace->get($request->user(), $request);
        abort_if($workspace === null, 404);

        return $workspace->forms()->whereKey($form->id)->firstOrFail();
    }
}
