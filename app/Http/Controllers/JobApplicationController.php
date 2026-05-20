<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

/**
 * JobApplicationController
 * 
 * Handles job application submissions and management.
 * - Public: Job listing and application submission
 * - HR/SuperAdmin: Application review and management
 */
class JobApplicationController extends Controller
{
    /**
     * Display list of open jobs (public view)
     */
    public function index()
    {
        $jobs = Job::open()
            ->with('department', 'position')
            ->orderByDesc('posted_date')
            ->paginate(10);

        return view('recruitment.jobs.index', compact('jobs'));
    }

    /**
     * Show job details and application form (public view)
     */
    public function show($jobId)
    {
        $job = Job::with('department', 'position', 'applications')
            ->findOrFail($jobId);

        if (!$job->is_open) {
            return back()->with('flash_danger', 'This job posting is no longer accepting applications.');
        }

        return view('recruitment.jobs.show', compact('job'));
    }

    /**
     * Store a new job application with resume upload
     */
    public function store(Request $request, $jobId)
    {
        $job = Job::findOrFail($jobId);

        // Validate application data
        $validated = $request->validate([
            'first_name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|max:20',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            'cover_letter' => 'nullable|string|max:2000',
        ]);

        try {
            // Upload resume file
            $resumePath = null;
            if ($request->hasFile('resume')) {
                $file = $request->file('resume');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $resumePath = $file->storeAs('applications/' . $jobId, $fileName, 'public');
            }

            // Create application record
            $application = JobApplication::create([
                'job_id' => $jobId,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'resume_path' => $resumePath,
                'cover_letter' => $validated['cover_letter'] ?? null,
                'status' => 'pending',
                'applied_date' => now(),
            ]);

            return back()->with('flash_success', 
                'Your application has been submitted successfully. We will review it and contact you soon.');
        } catch (\Exception $e) {
            return back()->with('flash_danger', 
                'Failed to submit application: ' . $e->getMessage());
        }
    }

    /**
     * Display applications for HR/SuperAdmin review
     */
    public function applications($jobId)
    {
        // Check authorization
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        $job = Job::with('applications')
            ->findOrFail($jobId);

        $applications = $job->applications()
            ->orderByDesc('applied_date')
            ->paginate(15);

        return view('recruitment.applications.index', compact('job', 'applications'));
    }

    /**
     * Show application details for HR/SuperAdmin
     */
    public function viewApplication($applicationId)
    {
        // Check authorization
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        $application = JobApplication::with('job', 'reviewedBy')
            ->findOrFail($applicationId);

        return view('recruitment.applications.show', compact('application'));
    }

    /**
     * Download resume file (HR/SuperAdmin only)
     */
    public function downloadResume($applicationId)
    {
        // Check authorization
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        $application = JobApplication::findOrFail($applicationId);

        if (!$application->resume_path || !Storage::disk('public')->exists($application->resume_path)) {
            return back()->with('flash_danger', 'Resume file not found.');
        }

        return Storage::disk('public')->download($application->resume_path);
    }

    /**
     * Update application status (HR/SuperAdmin only)
     */
    public function updateStatus(Request $request, $applicationId)
    {
        // Check authorization
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,shortlisted,rejected,hired',
            'notes' => 'nullable|string|max:1000',
        ]);

        $application = JobApplication::findOrFail($applicationId);

        $oldStatus = $application->status;
        $application->update([
            'status' => $validated['status'],
            'reviewed_by' => Auth::id(),
            'reviewed_date' => now(),
            'notes' => $validated['notes'] ?? $application->notes,
        ]);

        // Log the action
        \App\Models\AuditLog::log('updated', 'recruitment',
            "Application #{$application->id} status changed from {$oldStatus} to {$validated['status']}");

        return back()->with('flash_success', 
            "Application status updated to {$validated['status']}.");
    }

    /**
     * Delete application (HR/SuperAdmin only)
     */
    public function destroy($applicationId)
    {
        // Check authorization
        if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized');
        }

        $application = JobApplication::findOrFail($applicationId);

        // Delete resume file if exists
        if ($application->resume_path && Storage::disk('public')->exists($application->resume_path)) {
            Storage::disk('public')->delete($application->resume_path);
        }

        $application->delete();

        return back()->with('flash_success', 'Application deleted successfully.');
    }
}
