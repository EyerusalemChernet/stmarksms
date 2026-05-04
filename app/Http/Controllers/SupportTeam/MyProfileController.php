<?php

namespace App\Http\Controllers\SupportTeam;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\JobPosting;
use App\Models\JobApplication;
use App\Models\StaffPayroll;
use App\Models\PerformanceReview;
use App\Models\StaffAttendance;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * MyProfileController
 *
 * Self-service portal for all authenticated staff (teachers, hr_manager, admin).
 * Every method resolves the logged-in user's linked Employee record first.
 * If no employee record exists, the user is redirected with a clear message.
 *
 * Routes are protected by 'auth' middleware only — no hr_manager required.
 */
class MyProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    protected function resolveEmployee(): ?Employee
    {
        return Employee::with([
            'employmentDetails.department',
            'employmentDetails.position',
            'currentSalary',
            'currentShift.shift',
            'qualifications',
            'emergencyContacts',
        ])->where('user_id', auth()->id())->first();
    }

    protected function noEmployeeRedirect()
    {
        return redirect()->route('dashboard')
            ->with('flash_danger', 'No employee record is linked to your account. Contact HR.');
    }

    // ── MY PROFILE ───────────────────────────────────────────────────────────

    /**
     * Show the employee's own profile — read-only view of their HR record.
     */
    public function profile()
    {
        $employee = $this->resolveEmployee();
        if (!$employee) return $this->noEmployeeRedirect();

        $recentAttendance = StaffAttendance::where('employee_id', $employee->id)
            ->orderByDesc('date')->take(10)->get();

        $leaveBalances = LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)->get()->keyBy('leave_type');

        $pendingLeave = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'pending')->count();

        return view('pages.hr.self_service.profile', compact(
            'employee', 'recentAttendance', 'leaveBalances', 'pendingLeave'
        ));
    }

    // ── MY PAYSLIPS ──────────────────────────────────────────────────────────

    /**
     * List all payroll records for the logged-in employee.
     */
    public function payslips()
    {
        $employee = $this->resolveEmployee();
        if (!$employee) return $this->noEmployeeRedirect();

        $year = request('year', now()->year);

        $payrolls = StaffPayroll::where('employee_id', $employee->id)
            ->where('month', 'like', $year . '%')
            ->orderByDesc('month')
            ->get();

        // Derive available years from the month column (format Y-m)
        $years = StaffPayroll::where('employee_id', $employee->id)
            ->selectRaw("LEFT(month, 4) as yr")
            ->groupBy('yr')
            ->orderByDesc('yr')
            ->pluck('yr')
            ->filter();

        return view('pages.hr.self_service.payslips', compact('employee', 'payrolls', 'year', 'years'));
    }

    /**
     * Show a single payslip — employee can only see their own.
     */
    public function payslip($payrollId)
    {
        $employee = $this->resolveEmployee();
        if (!$employee) return $this->noEmployeeRedirect();

        $payroll = StaffPayroll::with(['items', 'approvedBy'])
            ->where('employee_id', $employee->id)
            ->where('id', $payrollId)
            ->first();

        if (!$payroll) {
            return redirect()->route('my.payslips')
                ->with('flash_danger', 'Payslip not found or you do not have access to it.');
        }

        return view('pages.hr.self_service.payslip', compact('employee', 'payroll'));
    }

    // ── MY PERFORMANCE ───────────────────────────────────────────────────────

    /**
     * Show the employee's own performance history.
     * Delegates to the existing PerformanceController logic.
     */
    public function performance()
    {
        $employee = $this->resolveEmployee();
        if (!$employee) return $this->noEmployeeRedirect();

        $reviews = PerformanceReview::with(['scores.category', 'reviewer'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('period')
            ->get();

        return view('pages.hr.self_service.performance', compact('employee', 'reviews'));
    }

    // ── JOB BOARD ────────────────────────────────────────────────────────────

    /**
     * Show open job postings — internal job board for staff.
     */
    public function jobBoard()
    {
        $postings = JobPosting::with(['department', 'position'])
            ->where('status', 'open')
            ->orderByDesc('created_at')
            ->get();

        return view('pages.hr.self_service.job_board', compact('postings'));
    }

    /**
     * Show a single job posting detail.
     */
    public function jobPosting($postingId)
    {
        $posting = JobPosting::with(['department', 'position'])
            ->where('status', 'open')
            ->findOrFail($postingId);

        // Check if the logged-in user already applied (by matching name/email from their user record)
        $user = auth()->user();
        $alreadyApplied = JobApplication::where('job_posting_id', $postingId)
            ->where(fn($q) => $q->where('email', $user->email)
                                ->orWhere('first_name', $user->name))
            ->exists();

        return view('pages.hr.self_service.job_posting', compact('posting', 'alreadyApplied'));
    }

    /**
     * Show the application form for a job posting.
     */
    public function applyForm($postingId)
    {
        $posting = JobPosting::where('status', 'open')->findOrFail($postingId);
        $user    = auth()->user();

        // Pre-fill from employee record if available
        $employee = Employee::where('user_id', auth()->id())->first();

        return view('pages.hr.self_service.apply', compact('posting', 'user', 'employee'));
    }

    /**
     * Submit a job application from the internal job board.
     */
    public function applyStore(Request $req, $postingId)
    {
        $posting = JobPosting::where('status', 'open')->findOrFail($postingId);

        $req->validate([
            'first_name'   => 'required|string|max:80',
            'last_name'    => 'required|string|max:80',
            'email'        => 'nullable|email|max:100',
            'phone'        => 'nullable|string|max:20',
            'cover_letter' => 'nullable|string|max:3000',
        ]);

        $application = JobApplication::create([
            'job_posting_id' => $postingId,
            'first_name'     => $req->first_name,
            'last_name'      => $req->last_name,
            'email'          => $req->email,
            'phone'          => $req->phone,
            'cover_letter'   => $req->cover_letter,
            'status'         => 'applied',
            'applied_at'     => now()->toDateString(),
        ]);

        // Record who submitted via a note
        \App\Models\ApplicationNote::create([
            'application_id'    => $application->id,
            'user_id'           => auth()->id(),
            'status_changed_to' => 'applied',
            'note'              => 'Internal application submitted by '.auth()->user()->name.' (user #'.auth()->id().').',
        ]);

        AuditLog::log('created', 'hr',
            "Internal job application #{$application->id} by user ".auth()->id()." for: {$posting->title}"
        );

        return redirect()->route('my.job_board')
            ->with('flash_success', "Application submitted for \"{$posting->title}\". HR will review it.");
    }
}
