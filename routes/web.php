<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\ApplicantManagementController;
use App\Http\Controllers\Admin\IndustryManagementController;
use App\Http\Controllers\Admin\MediaManagementController;
use App\Http\Controllers\Admin\NoteManagementController;
use App\Http\Controllers\Admin\OrganizationManagementController;
use App\Http\Controllers\Admin\PageImportController;
use App\Http\Controllers\Admin\PageManagementController;
use App\Http\Controllers\Admin\ReviewerAssignmentManagementController;
use App\Http\Controllers\Admin\ReviewerManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\SeasonManagementController;
use App\Http\Controllers\Admin\SettingsManagementController;
use App\Http\Controllers\Admin\StageManagementController;
use App\Http\Controllers\Admin\SubmissionManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportCenterController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\HandbookController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ReviewerQueueController;
use App\Http\Controllers\ScreeningReviewController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\SubmissionFileController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('handbook', HandbookController::class)->name('handbook.index');

Route::middleware(['auth', 'verified', 'permission:dashboard.view'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');

    Route::get('submissions/create', [SubmissionController::class, 'create'])->name('submissions.create');

    Route::post('submissions', [SubmissionController::class, 'store'])->name('submissions.store');

    Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');

    Route::get('submissions/{submission}/edit', [SubmissionController::class, 'edit'])->name('submissions.edit');

    Route::put('submissions/{submission}', [SubmissionController::class, 'update'])->name('submissions.update');

    Route::put('submissions/{submission}/autosave', [SubmissionController::class, 'autosave'])->name('submissions.autosave');

    Route::delete('submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');

    Route::post('submissions/{submission}/files', [SubmissionFileController::class, 'store'])->name('submission-files.store');

    Route::get('submission-files/{submissionFile}/download', [SubmissionFileController::class, 'download'])->name('submission-files.download');

    Route::delete('submission-files/{submissionFile}', [SubmissionFileController::class, 'destroy'])->name('submission-files.destroy');

    Route::get('search', GlobalSearchController::class)
        ->middleware('permission:search.view')
        ->name('search.index');

    Route::get('exports', [ExportCenterController::class, 'index'])
        ->middleware('permission:exports.view')
        ->name('exports.index');

    Route::get('reports', [ReportsController::class, 'index'])
        ->middleware('permission:reports.view')
        ->name('reports.index');

    Route::get('reports/pages.csv', [ReportsController::class, 'pagesCsv'])
        ->middleware('permission:reports.view')
        ->name('reports.pages.csv');

    Route::get('reviewer/queue', [ReviewerQueueController::class, 'index'])
        ->middleware('permission:reviewer-queue.view')
        ->name('reviewer-queue.index');

    Route::get('reviewer/assignments/{reviewerAssignment}', [ReviewerQueueController::class, 'show'])
        ->middleware('permission:reviewer-queue.view')
        ->name('reviewer-queue.show');

    Route::post('reviewer/assignments/{reviewerAssignment}/screening-review', [ScreeningReviewController::class, 'store'])
        ->middleware('permission:screening-reviews.create')
        ->name('screening-reviews.store');

    Route::get('exports/users.csv', [ExportCenterController::class, 'usersCsv'])
        ->middleware(['permission:exports.view', 'permission:users.view'])
        ->name('exports.users.csv');

    Route::get('exports/summary/print', [ExportCenterController::class, 'printSummary'])
        ->middleware('permission:exports.view')
        ->name('exports.summary.print');

    Route::get('admin/users', [UserManagementController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('users.index');

    Route::get('admin/applicants', [ApplicantManagementController::class, 'index'])
        ->middleware('permission:applicants.view')
        ->name('applicants.index');

    Route::get('admin/applicants/{applicant}/edit', [ApplicantManagementController::class, 'edit'])
        ->middleware('permission:applicants.view')
        ->name('applicants.edit');

    Route::put('admin/applicants/{applicant}', [ApplicantManagementController::class, 'update'])
        ->middleware('permission:applicants.update')
        ->name('applicants.update');

    Route::get('admin/organizations', [OrganizationManagementController::class, 'index'])
        ->middleware('permission:organizations.view')
        ->name('organizations.index');

    Route::get('admin/organizations/create', [OrganizationManagementController::class, 'create'])
        ->middleware('permission:organizations.create')
        ->name('organizations.create');

    Route::post('admin/organizations', [OrganizationManagementController::class, 'store'])
        ->middleware('permission:organizations.create')
        ->name('organizations.store');

    Route::get('admin/organizations/{organization}/edit', [OrganizationManagementController::class, 'edit'])
        ->middleware('permission:organizations.view')
        ->name('organizations.edit');

    Route::put('admin/organizations/{organization}', [OrganizationManagementController::class, 'update'])
        ->middleware('permission:organizations.update')
        ->name('organizations.update');

    Route::delete('admin/organizations/{organization}', [OrganizationManagementController::class, 'destroy'])
        ->middleware('permission:organizations.delete')
        ->name('organizations.destroy');

    Route::get('admin/submissions', [SubmissionManagementController::class, 'index'])
        ->middleware('permission:submissions.view')
        ->name('admin-submissions.index');

    Route::get('admin/submissions/{submission}', [SubmissionManagementController::class, 'show'])
        ->middleware('permission:submissions.view')
        ->name('admin-submissions.show');

    Route::post('admin/submissions/{submission}/status', [SubmissionManagementController::class, 'transition'])
        ->middleware('permission:submissions.update')
        ->name('admin-submissions.transition');

    Route::post('admin/submissions/{submission}/reviewer-assignments', [ReviewerAssignmentManagementController::class, 'store'])
        ->middleware('permission:reviewer-assignments.create')
        ->name('admin-submissions.reviewer-assignments.store');

    Route::delete('admin/submissions/{submission}/reviewer-assignments/{reviewerAssignment}', [ReviewerAssignmentManagementController::class, 'destroy'])
        ->middleware('permission:reviewer-assignments.update')
        ->name('admin-submissions.reviewer-assignments.destroy');

    Route::get('admin/reviewers', [ReviewerManagementController::class, 'index'])
        ->middleware('permission:reviewers.view')
        ->name('reviewers.index');

    Route::get('admin/reviewers/create', [ReviewerManagementController::class, 'create'])
        ->middleware('permission:reviewers.create')
        ->name('reviewers.create');

    Route::post('admin/reviewers', [ReviewerManagementController::class, 'store'])
        ->middleware('permission:reviewers.create')
        ->name('reviewers.store');

    Route::get('admin/reviewers/{reviewer}/edit', [ReviewerManagementController::class, 'edit'])
        ->middleware('permission:reviewers.view')
        ->name('reviewers.edit');

    Route::put('admin/reviewers/{reviewer}', [ReviewerManagementController::class, 'update'])
        ->middleware('permission:reviewers.update')
        ->name('reviewers.update');

    Route::get('admin/seasons', [SeasonManagementController::class, 'index'])
        ->middleware('permission:seasons.view')
        ->name('seasons.index');

    Route::get('admin/seasons/create', [SeasonManagementController::class, 'create'])
        ->middleware('permission:seasons.create')
        ->name('seasons.create');

    Route::post('admin/seasons', [SeasonManagementController::class, 'store'])
        ->middleware('permission:seasons.create')
        ->name('seasons.store');

    Route::get('admin/seasons/{season}/edit', [SeasonManagementController::class, 'edit'])
        ->middleware('permission:seasons.update')
        ->name('seasons.edit');

    Route::put('admin/seasons/{season}', [SeasonManagementController::class, 'update'])
        ->middleware('permission:seasons.update')
        ->name('seasons.update');

    Route::post('admin/seasons/{season}/activate', [SeasonManagementController::class, 'activate'])
        ->middleware('permission:seasons.update')
        ->name('seasons.activate');

    Route::post('admin/seasons/{season}/close', [SeasonManagementController::class, 'close'])
        ->middleware('permission:seasons.update')
        ->name('seasons.close');

    Route::delete('admin/seasons/{season}', [SeasonManagementController::class, 'destroy'])
        ->middleware('permission:seasons.delete')
        ->name('seasons.destroy');

    Route::get('admin/stages', [StageManagementController::class, 'index'])
        ->middleware('permission:stages.view')
        ->name('stages.index');

    Route::get('admin/stages/create', [StageManagementController::class, 'create'])
        ->middleware('permission:stages.create')
        ->name('stages.create');

    Route::post('admin/stages', [StageManagementController::class, 'store'])
        ->middleware('permission:stages.create')
        ->name('stages.store');

    Route::get('admin/stages/{stage}/edit', [StageManagementController::class, 'edit'])
        ->middleware('permission:stages.update')
        ->name('stages.edit');

    Route::put('admin/stages/{stage}', [StageManagementController::class, 'update'])
        ->middleware('permission:stages.update')
        ->name('stages.update');

    Route::post('admin/stages/{stage}/open', [StageManagementController::class, 'open'])
        ->middleware('permission:stages.update')
        ->name('stages.open');

    Route::post('admin/stages/{stage}/close', [StageManagementController::class, 'close'])
        ->middleware('permission:stages.update')
        ->name('stages.close');

    Route::delete('admin/stages/{stage}', [StageManagementController::class, 'destroy'])
        ->middleware('permission:stages.delete')
        ->name('stages.destroy');

    Route::get('admin/industries', [IndustryManagementController::class, 'index'])
        ->middleware('permission:industries.view')
        ->name('industries.index');

    Route::get('admin/industries/create', [IndustryManagementController::class, 'create'])
        ->middleware('permission:industries.create')
        ->name('industries.create');

    Route::post('admin/industries', [IndustryManagementController::class, 'store'])
        ->middleware('permission:industries.create')
        ->name('industries.store');

    Route::get('admin/industries/{industry}/edit', [IndustryManagementController::class, 'edit'])
        ->middleware('permission:industries.update')
        ->name('industries.edit');

    Route::put('admin/industries/{industry}', [IndustryManagementController::class, 'update'])
        ->middleware('permission:industries.update')
        ->name('industries.update');

    Route::post('admin/industries/{industry}/toggle', [IndustryManagementController::class, 'toggle'])
        ->middleware('permission:industries.update')
        ->name('industries.toggle');

    Route::delete('admin/industries/{industry}', [IndustryManagementController::class, 'destroy'])
        ->middleware('permission:industries.delete')
        ->name('industries.destroy');

    Route::get('admin/pages', [PageManagementController::class, 'index'])
        ->middleware('permission:pages.view')
        ->name('pages.index');

    Route::get('admin/pages/import', [PageImportController::class, 'index'])
        ->middleware('permission:pages.create')
        ->name('pages.import');

    Route::post('admin/pages/import/preview', [PageImportController::class, 'preview'])
        ->middleware('permission:pages.create')
        ->name('pages.import.preview');

    Route::post('admin/pages/import', [PageImportController::class, 'store'])
        ->middleware('permission:pages.create')
        ->name('pages.import.store');

    Route::get('admin/settings', [SettingsManagementController::class, 'edit'])
        ->middleware('permission:settings.view')
        ->name('admin-settings.edit');

    Route::put('admin/settings', [SettingsManagementController::class, 'update'])
        ->middleware('permission:settings.update')
        ->name('admin-settings.update');

    Route::get('admin/media', [MediaManagementController::class, 'index'])
        ->middleware('permission:media.view')
        ->name('media.index');

    Route::post('admin/media', [MediaManagementController::class, 'store'])
        ->middleware('permission:media.create')
        ->name('media.store');

    Route::get('admin/media/{media}/download', [MediaManagementController::class, 'download'])
        ->middleware('permission:media.view')
        ->name('media.download');

    Route::delete('admin/media/{media}', [MediaManagementController::class, 'destroy'])
        ->middleware('permission:media.delete')
        ->name('media.destroy');

    Route::post('admin/notes', [NoteManagementController::class, 'store'])
        ->middleware('permission:notes.create')
        ->name('notes.store');

    Route::delete('admin/notes/{note}', [NoteManagementController::class, 'destroy'])
        ->middleware('permission:notes.delete')
        ->name('notes.destroy');

    Route::get('admin/pages/create', [PageManagementController::class, 'create'])
        ->middleware('permission:pages.create')
        ->name('pages.create');

    Route::post('admin/pages', [PageManagementController::class, 'store'])
        ->middleware('permission:pages.create')
        ->name('pages.store');

    Route::get('admin/pages/{page}/edit', [PageManagementController::class, 'edit'])
        ->middleware('permission:pages.update')
        ->name('pages.edit');

    Route::put('admin/pages/{page}', [PageManagementController::class, 'update'])
        ->middleware('permission:pages.update')
        ->name('pages.update');

    Route::delete('admin/pages/{page}', [PageManagementController::class, 'destroy'])
        ->middleware('permission:pages.delete')
        ->name('pages.destroy');

    Route::post('admin/pages/{page}/restore', [PageManagementController::class, 'restore'])
        ->middleware('permission:pages.delete')
        ->name('pages.restore');

    Route::get('admin/users/create', [UserManagementController::class, 'create'])
        ->middleware('permission:users.create')
        ->name('users.create');

    Route::post('admin/users', [UserManagementController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');

    Route::get('admin/users/{user}/edit', [UserManagementController::class, 'edit'])
        ->middleware('permission:users.update')
        ->name('users.edit');

    Route::put('admin/users/{user}', [UserManagementController::class, 'update'])
        ->middleware('permission:users.update')
        ->name('users.update');

    Route::put('admin/users/{user}/roles', [UserManagementController::class, 'updateRoles'])
        ->middleware('permission:users.update')
        ->name('users.roles.update');

    Route::delete('admin/users/{user}', [UserManagementController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');

    Route::get('admin/roles', [RoleManagementController::class, 'index'])
        ->middleware('permission:roles.view')
        ->name('roles.index');

    Route::get('admin/roles/create', [RoleManagementController::class, 'create'])
        ->middleware('permission:roles.create')
        ->name('roles.create');

    Route::post('admin/roles', [RoleManagementController::class, 'store'])
        ->middleware('permission:roles.create')
        ->name('roles.store');

    Route::get('admin/roles/{role}/edit', [RoleManagementController::class, 'edit'])
        ->middleware('permission:roles.update')
        ->name('roles.edit');

    Route::put('admin/roles/{role}', [RoleManagementController::class, 'update'])
        ->middleware('permission:roles.update')
        ->name('roles.update');

    Route::put('admin/roles/{role}/permissions', [RoleManagementController::class, 'updatePermissions'])
        ->middleware('permission:roles.update')
        ->name('roles.permissions.update');

    Route::delete('admin/roles/{role}', [RoleManagementController::class, 'destroy'])
        ->middleware('permission:roles.delete')
        ->name('roles.destroy');

    Route::get('notifications', [NotificationController::class, 'index'])
        ->middleware('permission:notifications.view')
        ->name('notifications.index');

    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])
        ->middleware('permission:notifications.view')
        ->name('notifications.read');

    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])
        ->middleware('permission:notifications.view')
        ->name('notifications.read-all');

    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:activity-logs.view')
        ->name('activity-logs.index');

    Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])
        ->middleware('permission:activity-logs.view')
        ->name('activity-logs.show');
});

require __DIR__.'/settings.php';

Route::get('{page:slug}', PublicPageController::class)->name('public-pages.show');
