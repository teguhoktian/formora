<?php

use App\Http\Controllers\FormController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('f/{slug}', [PublicFormController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('public.forms.show');
Route::post('f/{slug}', [PublicFormController::class, 'submit'])
    ->middleware('throttle:10,1')
    ->where('slug', '[a-z0-9-]+')
    ->name('public.forms.submit');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('forms', [FormController::class, 'index'])->name('forms.index');
    Route::post('forms', [FormController::class, 'store'])->name('forms.store');
    Route::get('forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::get('forms/{form}/submissions', [SubmissionController::class, 'index'])->name('forms.submissions.index');
    Route::post('forms/{form}/submissions/export', [SubmissionController::class, 'export'])->name('forms.submissions.export');
    Route::get('forms/{form}/submissions/exports/{export}', [SubmissionController::class, 'download'])->name('forms.submissions.exports.download');
    Route::get('forms/{form}/submissions/{submission}', [SubmissionController::class, 'show'])->name('forms.submissions.show');
    Route::patch('forms/{form}/submissions/{submission}', [SubmissionController::class, 'update'])->name('forms.submissions.update');

    Route::post('workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('workspaces/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::post('workspaces/{workspace}/members', [WorkspaceController::class, 'storeMember'])->name('workspaces.members.store');
    Route::patch('workspaces/{workspace}/members/{member}', [WorkspaceController::class, 'updateMember'])->name('workspaces.members.update');
    Route::delete('workspaces/{workspace}/members/{member}', [WorkspaceController::class, 'destroyMember'])->name('workspaces.members.destroy');
    Route::post('workspaces/{workspace}/ownership', [WorkspaceController::class, 'transferOwnership'])->name('workspaces.ownership.transfer');
});

require __DIR__.'/settings.php';
