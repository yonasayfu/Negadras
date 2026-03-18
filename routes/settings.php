<?php

use App\Http\Controllers\Settings\ApplicantProfileController;
use App\Http\Controllers\Settings\OrganizationProfileController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('settings/applicant-profile', [ApplicantProfileController::class, 'edit'])->name('applicant-profile.edit');
    Route::put('settings/applicant-profile', [ApplicantProfileController::class, 'update'])->name('applicant-profile.update');
    Route::get('settings/organization-profile', [OrganizationProfileController::class, 'edit'])->name('organization-profile.edit');
    Route::put('settings/organization-profile', [OrganizationProfileController::class, 'update'])->name('organization-profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});
