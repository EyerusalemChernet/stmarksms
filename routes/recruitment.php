<?php

use App\Http\Controllers\JobController;
use App\Http\Controllers\JobApplicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Recruitment Routes
|--------------------------------------------------------------------------
|
| Routes for job postings and applications
|
*/

// Public routes - Job listing and application submission
Route::prefix('recruitment')->group(function () {
    // Public job listing
    Route::get('jobs', [JobApplicationController::class, 'index'])->name('recruitment.jobs.index');
    Route::get('jobs/{jobId}', [JobApplicationController::class, 'show'])->name('recruitment.jobs.show');
    
    // Public job application submission
    Route::post('jobs/{jobId}/apply', [JobApplicationController::class, 'store'])->name('recruitment.apply');
});

// HR/SuperAdmin routes - Job and application management
Route::middleware(['auth', 'role:super_admin|admin'])->prefix('recruitment')->group(function () {
    // Job management
    Route::resource('jobs', JobController::class);
    
    // Application management
    Route::get('jobs/{jobId}/applications', [JobApplicationController::class, 'applications'])->name('recruitment.applications.index');
    Route::get('applications/{applicationId}', [JobApplicationController::class, 'viewApplication'])->name('recruitment.applications.show');
    Route::get('applications/{applicationId}/download-resume', [JobApplicationController::class, 'downloadResume'])->name('recruitment.applications.download-resume');
    Route::put('applications/{applicationId}/status', [JobApplicationController::class, 'updateStatus'])->name('recruitment.applications.update-status');
    Route::delete('applications/{applicationId}', [JobApplicationController::class, 'destroy'])->name('recruitment.applications.destroy');
});
