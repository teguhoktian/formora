<?php

namespace App\Jobs;

use App\Models\FormSubmission;
use App\Models\FormVersion;
use App\Models\SubmissionExport;
use App\Services\SubmissionResponsePresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateSubmissionExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $exportId) {}

    public function handle(SubmissionResponsePresenter $presenter): void
    {
        $export = SubmissionExport::query()->findOrFail($this->exportId);
        $filters = $export->filters;
        $path = 'submission-exports/'.$export->id.'.csv';
        Storage::disk('local')->makeDirectory('submission-exports');
        $stream = fopen(Storage::disk('local')->path($path), 'wb');
        if ($stream === false) {
            throw new \RuntimeException('Could not open private export file.');
        }
        try {
            $columns = [];
            FormVersion::query()
                ->where('form_id', $export->form_id)
                ->orderBy('version_number')
                ->get(['id', 'schema'])
                ->each(function (FormVersion $version) use (&$columns, $presenter): void {
                    foreach ($presenter->fields($version->schema) as $name => $field) {
                        $columns[$name] ??= $field['label'];
                    }
                });

            fputcsv($stream, array_map($this->safeCsvCell(...), ['Referensi', 'Status', 'Dikirim', ...array_values($columns)]));
            $fieldIndexes = array_flip(array_keys($columns));

            $query = FormSubmission::query()
                ->select(['id', 'workspace_id', 'form_id', 'form_version_id', 'status', 'reference_number', 'response_data', 'created_at'])
                ->with('formVersion:id,schema')
                ->where('form_id', $export->form_id)
                ->where('workspace_id', $export->workspace_id)
                ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
                ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
                ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(function ($q) use ($search): void {
                    $q->whereRaw('CAST(response_data AS TEXT) LIKE ?', ['%'.$search.'%'])
                        ->orWhere('reference_number', 'like', '%'.$search.'%');
                }))
                ->orderBy('id');

            $query->chunkById(200, function ($submissions) use ($stream, $presenter, $columns, $fieldIndexes): void {
                foreach ($submissions as $submission) {
                    $fields = $presenter->fields($submission->formVersion->schema);
                    $row = [
                        $submission->reference_number,
                        $submission->status,
                        $submission->created_at?->toIso8601String(),
                        ...array_fill(0, count($columns), ''),
                    ];
                    foreach ($submission->response_data as $name => $value) {
                        if (! isset($fieldIndexes[$name])) {
                            continue;
                        }
                        $label = $fields[$name]['label'] ?? $columns[$name];
                        $row[$fieldIndexes[$name] + 3] = $presenter->mask((string) $name, $label, $value, $fields[$name]['type'] ?? null);
                    }
                    fputcsv($stream, array_map($this->safeCsvCell(...), $row));
                }
            });

            $export->update(['status' => 'ready', 'file_path' => $path]);
        } catch (Throwable $exception) {
            $export->update(['status' => 'failed']);
            Storage::disk('local')->delete($path);
            throw $exception;
        } finally {
            fclose($stream);
        }
    }

    private function safeCsvCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[\s\x00-\x1F]*[=+@-]/', $value) ? "'".$value : $value;
    }
}
