<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormVersion;
use App\Services\FormSchemaService;
use App\Services\IndonesiaRegionCatalog;
use App\Services\PublicSubmissionService;
use App\Services\SmartFieldRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function show(string $slug, IndonesiaRegionCatalog $regions): View
    {
        [$form, $version] = $this->publishedForm($slug);
        $schema = app(FormSchemaService::class)->validate($version->schema);
        $failedPage = session('failed_page', 0);
        $failedPage = is_numeric($failedPage) ? (int) $failedPage : 0;
        $failedPage = max(0, min($failedPage, count($schema['pages']) - 1));

        return view('public.forms.show', [
            'form' => $form,
            'schema' => $schema,
            'failedPage' => $failedPage,
            'referenceNumber' => session()->pull('submission_reference'),
            'smartFieldDefinitions' => app(SmartFieldRegistry::class)->definitions(),
            'provinces' => $regions->provinces(),
        ]);
    }

    public function regencies(string $provinceCode, IndonesiaRegionCatalog $regions): JsonResponse
    {
        return response()->json(['data' => $regions->regencies($provinceCode)]);
    }

    public function districts(string $regencyCode, IndonesiaRegionCatalog $regions): JsonResponse
    {
        return response()->json(['data' => $regions->districts($regencyCode)]);
    }

    public function villages(string $districtCode, IndonesiaRegionCatalog $regions): JsonResponse
    {
        return response()->json(['data' => $regions->villages($districtCode)]);
    }

    public function submit(Request $request, string $slug, PublicSubmissionService $submissions, FormSchemaService $schemas): JsonResponse|RedirectResponse
    {
        [$form, $version] = $this->publishedForm($slug);
        $pageCount = max(1, count($version->schema['pages'] ?? []));
        $page = max(0, min($request->integer('current_page'), $pageCount - 1));

        if ($request->filled('website')) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['form' => ['Pengiriman tidak dapat diproses.']], 'failed_page' => $page], 422);
            }

            return back()->withErrors(['form' => 'Pengiriman tidak dapat diproses.'])->with('failed_page', $page);
        }

        try {
            $validated = $submissions->validate($version, $request->all(), $schemas);
        } catch (ValidationException $exception) {
            foreach ($version->schema['pages'] ?? [] as $pageIndex => $schemaPage) {
                foreach ($schemaPage['fields'] ?? [] as $field) {
                    $fieldName = $field['name'] ?? null;
                    if (! is_string($fieldName)) {
                        continue;
                    }

                    $hasFieldError = isset($exception->errors()['answers.'.$fieldName]);
                    foreach (array_keys($exception->errors()) as $errorKey) {
                        if (is_string($errorKey) && str_starts_with($errorKey, 'answers.'.$fieldName.'.')) {
                            $hasFieldError = true;
                            break;
                        }
                    }

                    if ($hasFieldError) {
                        $page = (int) $pageIndex;
                        break 2;
                    }
                }
            }

            if ($request->expectsJson()) {
                return response()->json(['errors' => $exception->errors(), 'failed_page' => $page], 422);
            }

            return back()->withErrors($exception->errors())->with('failed_page', $page);
        }

        $submission = $submissions->store($form, $version, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'reference_number' => $submission->reference_number,
                'message' => $version->schema['settings']['success_message'] ?? 'Terima kasih, jawaban Anda berhasil dikirim.',
            ], 201);
        }

        return to_route('public.forms.show', $form->slug)
            ->with('submission_reference', $submission->reference_number);
    }

    /** @return array{Form, FormVersion} */
    private function publishedForm(string $slug): array
    {
        $form = Form::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_version_id')
            ->whereHas('publishedVersion', fn ($query) => $query->where('is_published', true))
            ->with('publishedVersion')
            ->firstOrFail();

        return [$form, $form->publishedVersion];
    }
}
