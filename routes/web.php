<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormTemplateController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\SmartFieldsController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('privacy', 'privacy')->name('privacy');

Route::get('f/{slug}', [PublicFormController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('public.forms.show');
Route::post('f/{slug}', [PublicFormController::class, 'submit'])
    ->middleware('throttle:10,1')
    ->where('slug', '[a-z0-9-]+')
    ->name('public.forms.submit');
Route::get('smart-fields/regions/{provinceCode}/regencies', [PublicFormController::class, 'regencies'])
    ->where('provinceCode', '[0-9]{2}')
    ->name('public.smart-fields.regencies');
Route::get('smart-fields/regions/{regencyCode}/districts', [PublicFormController::class, 'districts'])
    ->where('regencyCode', '[0-9]{2}\\.[0-9]{2}')
    ->name('public.smart-fields.districts');
Route::get('smart-fields/regions/{districtCode}/villages', [PublicFormController::class, 'villages'])
    ->where('districtCode', '[0-9]{2}\\.[0-9]{2}\\.[0-9]{2}')
    ->name('public.smart-fields.villages');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('smart-fields', SmartFieldsController::class)->name('smart-fields.index');
    Route::get('analytics', AnalyticsController::class)->name('analytics.index');
    Route::get('responses', [SubmissionController::class, 'workspaceIndex'])->name('responses.index');
    Route::get('workspace', [WorkspaceController::class, 'index'])->name('workspaces.index');

    Route::get('forms', [FormController::class, 'index'])->name('forms.index');
    Route::post('forms', [FormController::class, 'store'])->name('forms.store');
    Route::get('forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::get('forms/{form}/submissions', [SubmissionController::class, 'index'])->name('forms.submissions.index');
    Route::post('forms/{form}/submissions/export', [SubmissionController::class, 'export'])->middleware('throttle:5,1')->name('forms.submissions.export');
    Route::get('forms/{form}/submissions/exports/{export}', [SubmissionController::class, 'download'])->name('forms.submissions.exports.download');
    Route::get('forms/{form}/submissions/{submission}/files/{fieldName}/{fileIndex}', [SubmissionController::class, 'downloadFile'])
        ->where(['fieldName' => '[a-z][a-z0-9_]{0,63}', 'fileIndex' => '[0-9]{1,3}'])
        ->name('forms.submissions.files.download');
    Route::get('forms/{form}/submissions/{submission}', [SubmissionController::class, 'show'])->name('forms.submissions.show');
    Route::patch('forms/{form}/submissions/{submission}', [SubmissionController::class, 'update'])->name('forms.submissions.update');

    Route::get('templates', [FormTemplateController::class, 'index'])->name('templates.index');
    Route::post('templates/from-form', [FormTemplateController::class, 'saveFromForm'])->name('templates.from-form');
    Route::get('templates/moderation', [FormTemplateController::class, 'moderationQueue'])->name('templates.moderation');
    Route::post('templates/{slug}/moderation', [FormTemplateController::class, 'moderate'])->name('templates.moderate');
    Route::get('templates/{slug}', [FormTemplateController::class, 'show'])->name('templates.show');
    Route::post('templates/{slug}/use', [FormTemplateController::class, 'use'])->name('templates.use');
    Route::put('templates/{slug}', [FormTemplateController::class, 'update'])->name('templates.update');
    Route::post('templates/{slug}/versions', [FormTemplateController::class, 'createVersion'])->name('templates.versions.store');

    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('workspaces/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::post('workspaces/{workspace}/members', [WorkspaceController::class, 'storeMember'])->name('workspaces.members.store');
    Route::patch('workspaces/{workspace}/members/{member}', [WorkspaceController::class, 'updateMember'])->name('workspaces.members.update');
    Route::delete('workspaces/{workspace}/members/{member}', [WorkspaceController::class, 'destroyMember'])->name('workspaces.members.destroy');
    Route::post('workspaces/{workspace}/ownership', [WorkspaceController::class, 'transferOwnership'])->name('workspaces.ownership.transfer');
});

require __DIR__.'/settings.php';
