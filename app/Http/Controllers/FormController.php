<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\CurrentWorkspace;
use App\Services\FormSchemaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FormController extends Controller
{
    public function index(Request $request, CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->get($request->user(), $request);
        $forms = $workspace?->forms()->latest()->paginate(20);

        return view('forms.index', [
            'workspace' => $workspace,
            'forms' => $forms,
            'canCreate' => $workspace !== null && $request->user()->can('create', [Form::class, $workspace]),
        ]);
    }

    public function store(Request $request, CurrentWorkspace $currentWorkspace, FormSchemaService $schemas): RedirectResponse
    {
        $workspace = $currentWorkspace->get($request->user(), $request);
        abort_if($workspace === null, 422, 'Pilih atau buat workspace terlebih dahulu.');
        Gate::authorize('create', [Form::class, $workspace]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $form = DB::transaction(function () use ($workspace, $validated, $schemas): Form {
            $form = $workspace->forms()->create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => 'draft',
            ]);
            $form->versions()->create([
                'version_number' => 1,
                'schema' => $schemas->empty(),
                'is_published' => false,
            ]);

            return $form;
        });

        return to_route('forms.edit', $form);
    }

    public function edit(Form $form, Request $request, CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->get($request->user(), $request);
        abort_if($workspace === null, 404);
        $form = $workspace->forms()->whereKey($form->getKey())->firstOrFail();
        Gate::authorize('update', $form);

        return view('forms.builder', ['formId' => $form->id, 'formTitle' => $form->title]);
    }
}
