<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * JobController
 * 
 * Handles job posting management for HR/SuperAdmin.
 * - Create, read, update, delete job postings
 * - View applications for each job
 */
class JobController extends Controller
{
    /**
     * Constructor - Apply middleware for authorization
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->hasAnyRole(['super_admin', 'admin'])) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }

    /**
     * Display list of all job postings (HR view)
     */
    public function index()
    {
        $jobs = Job::with('department', 'position', 'postedBy')
            ->orderByDesc('posted_date')
            ->paginate(15);

        return view('recruitment.jobs.admin-index', compact('jobs'));
    }

    /**
     * Show form to create new job posting
     */
    public function create()
    {
        $departments = Department::all();
        $positions = Position::all();

        return view('recruitment.jobs.create', compact('departments', 'positions'));
    }

    /**
     * Store new job posting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:3',
            'employment_type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:150',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'closing_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
        ]);

        $job = Job::create([
            ...$validated,
            'posted_by' => Auth::id(),
            'posted_date' => now()->toDateString(),
            'status' => 'open',
        ]);

        \App\Models\AuditLog::log('created', 'recruitment',
            "Job posting created: {$job->title}");

        return redirect()->route('jobs.show', $job->id)
            ->with('flash_success', 'Job posting created successfully.');
    }

    /**
     * Show job posting details
     */
    public function show($jobId)
    {
        $job = Job::with('department', 'position', 'postedBy', 'applications')
            ->findOrFail($jobId);

        return view('recruitment.jobs.admin-show', compact('job'));
    }

    /**
     * Show form to edit job posting
     */
    public function edit($jobId)
    {
        $job = Job::findOrFail($jobId);
        $departments = Department::all();
        $positions = Position::all();

        return view('recruitment.jobs.edit', compact('job', 'departments', 'positions'));
    }

    /**
     * Update job posting
     */
    public function update(Request $request, $jobId)
    {
        $job = Job::findOrFail($jobId);

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:3',
            'employment_type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:150',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'status' => 'required|in:open,closed,archived',
            'closing_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $job->update($validated);

        \App\Models\AuditLog::log('updated', 'recruitment',
            "Job posting updated: {$job->title}");

        return redirect()->route('jobs.show', $job->id)
            ->with('flash_success', 'Job posting updated successfully.');
    }

    /**
     * Delete job posting
     */
    public function destroy($jobId)
    {
        $job = Job::findOrFail($jobId);

        $job->delete();

        \App\Models\AuditLog::log('deleted', 'recruitment',
            "Job posting deleted: {$job->title}");

        return redirect()->route('jobs.index')
            ->with('flash_success', 'Job posting deleted successfully.');
    }
}
