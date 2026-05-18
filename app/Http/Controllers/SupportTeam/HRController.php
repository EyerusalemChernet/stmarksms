<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeQualification;
use App\Models\EmploymentDetails;
use App\Models\EthiopianHoliday;
use App\Models\JobPosting;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\Shift;
use App\Models\StaffAttendance;
use App\Models\StaffPayroll;
use App\Models\StaffPosition;
use App\Models\StaffSalary;
use App\Models\StaffShift;
use App\Models\Subject;
use App\Services\AttendanceService;
use App\Services\EmployeeProfileService;
use App\Services\EthiopianHolidayService;
use App\Services\PayrollService;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;

class HRController extends Controller
{
    protected EmployeeProfileService $profileService;
    protected AttendanceService $attendanceService;
    protected PayrollService $payrollService;
    protected EthiopianHolidayService $holidayService;

    public function __construct(
        EmployeeProfileService $profileService,
        AttendanceService $attendanceService,
        PayrollService $payrollService,
        EthiopianHolidayService $holidayService
    ) {
        $this->middleware('hr_manager');
        $this->profileService    = $profileService;
        $this->attendanceService = $attendanceService;
        $this->payrollService    = $payrollService;
        $this->holidayService    = $holidayService;
    }

    // ── HR DASHBOARD ─────────────────────────────────────────────────────────

    public function dashboard()
    {
        $today = now()->toDateString();
        $month = now()->format('Y-m');

        // ── Headcount ────────────────────────────────────────────────────────
        $totalActive     = Employee::where('status', 'active')->count();
        $totalOnLeave    = Employee::where('status', 'on_leave')->count();
        $totalSuspended  = Employee::where('status', 'suspended')->count();
        $totalTerminated = Employee::where('status', 'terminated')->count();

        // ── Today's attendance ───────────────────────────────────────────────
        $todayPresent = StaffAttendance::where('date', $today)
            ->whereIn('status', ['present', 'late'])
            ->whereNotNull('employee_id')->count();
        $todayAbsent  = StaffAttendance::where('date', $today)
            ->where('status', 'absent')
            ->whereNotNull('employee_id')->count();
        $todayLate    = StaffAttendance::where('date', $today)
            ->where('status', 'late')
            ->whereNotNull('employee_id')->count();
        $todayOnLeave = StaffAttendance::where('date', $today)
            ->where('status', 'leave')
            ->whereNotNull('employee_id')->count();

        // Attendance rate today (out of active employees)
        $attRate = $totalActive > 0
            ? round(($todayPresent / $totalActive) * 100, 1)
            : 0;

        // ── Payroll this month ───────────────────────────────────────────────
        $payrollCounts = StaffPayroll::where('month', $month)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $payrollDraft    = $payrollCounts->get('draft', 0);
        $payrollApproved = $payrollCounts->get('approved', 0);
        $payrollPaid     = $payrollCounts->get('paid', 0);
        $totalNetPay     = StaffPayroll::where('month', $month)
            ->where('status', 'paid')->sum('net_pay');

        // ── Leave requests ───────────────────────────────────────────────────
        $pendingLeave   = LeaveRequest::where('status', 'pending')->count();
        $approvedLeave  = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)->count();
        $recentLeave    = LeaveRequest::with('employee')
            ->where('status', 'pending')
            ->orderByDesc('created_at')->take(5)->get();

        // ── Recruitment ──────────────────────────────────────────────────────
        $openPostings   = JobPosting::where('status', 'open')->count();
        $newApplications = \App\Models\JobApplication::where('status', 'applied')
            ->where('created_at', '>=', now()->subDays(7))->count();

        // ── Department breakdown ─────────────────────────────────────────────
        $deptBreakdown = Department::withCount(['employees as active_count' => function ($q) {
            $q->where('status', 'active');
        }])->orderByDesc('active_count')->take(6)->get();

        // ── Recent hires (last 30 days) ──────────────────────────────────────
        $recentHires = Employee::with('employmentDetails.department')
            ->where('status', 'active')
            ->whereHas('employmentDetails', fn($q) =>
                $q->where('hire_date', '>=', now()->subDays(30))
            )
            ->orderByDesc('created_at')->take(5)->get();

        // ── Monthly attendance trend (last 6 months) ─────────────────────────
        $attendanceTrend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $m   = now()->subMonths($i)->format('Y-m');
            $lbl = now()->subMonths($i)->format('M Y');
            $total   = StaffAttendance::where('date', 'like', $m . '%')
                ->whereNotNull('employee_id')->count();
            $present = StaffAttendance::where('date', 'like', $m . '%')
                ->whereNotNull('employee_id')
                ->whereIn('status', ['present', 'late'])->count();
            $attendanceTrend->push([
                'month'   => $lbl,
                'rate'    => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                'present' => $present,
                'total'   => $total,
            ]);
        }

        // ── Unlinked staff users (no Employee record) ───────────────────────
        $staffTypes     = ['teacher', 'hr_manager', 'admin', 'super_admin'];
        $linkedUserIds  = Employee::whereNotNull('user_id')->pluck('user_id');
        $unlinkedCount  = User::whereIn('user_type', $staffTypes)
            ->whereNotIn('id', $linkedUserIds)->count();

        // ── Expiring contracts ───────────────────────────────────────────────
        $expiringContractsCount = EmploymentDetails::whereHas('employee', fn($q) => $q->where('status','active'))
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [now(), now()->addDays(30)])->count();
        $expiredContractsCount  = EmploymentDetails::whereHas('employee', fn($q) => $q->where('status','active'))
            ->whereNotNull('contract_end_date')
            ->where('contract_end_date', '<', now())->count();

        return view('pages.hr.dashboard', compact(
            'totalActive', 'totalOnLeave', 'totalSuspended', 'totalTerminated',
            'todayPresent', 'todayAbsent', 'todayLate', 'todayOnLeave', 'attRate',
            'payrollDraft', 'payrollApproved', 'payrollPaid', 'totalNetPay', 'month',
            'pendingLeave', 'approvedLeave', 'recentLeave',
            'openPostings', 'newApplications',
            'deptBreakdown', 'recentHires', 'attendanceTrend', 'today',
            'unlinkedCount', 'expiringContractsCount', 'expiredContractsCount'
        ));
    }

    // ── EMPLOYEE LIST ────────────────────────────────────────────────────────

    public function index(Request $req)
    {
        $status = $req->get('status', 'active');
        $search = trim($req->get('search', ''));

        $query = Employee::with(['employmentDetails.department','employmentDetails.position','currentSalary'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('first_name',    'like', "%{$search}%")
                          ->orWhere('last_name',   'like', "%{$search}%")
                          ->orWhere('email',        'like', "%{$search}%")
                          ->orWhere('employee_code','like', "%{$search}%")
                          ->orWhere('phone',        'like', "%{$search}%");
                });
            })
            ->orderBy('first_name');

        $statusCounts = array_merge(
            ['active'=>0,'on_leave'=>0,'suspended'=>0,'terminated'=>0],
            Employee::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status')->toArray()
        );

        // ── Export ───────────────────────────────────────────────────────────
        if ($req->get('export') === 'pdf') {
            $employees = $query->get();
            $pdf = PDF::loadView('pages.hr.exports.staff_pdf', compact('employees','status','search'));
            return $pdf->setPaper('a4','landscape')->download("employees_{$status}.pdf");
        }

        if ($req->get('export') === 'csv') {
            $employees = $query->get();
            return $this->exportStaffCsv($employees, $status);
        }

        $employees = $query->get();
        return view('pages.hr.index', compact('employees','status','statusCounts','search'));
    }

    protected function exportStaffCsv($employees, $status)
    {
        $filename = "employees_{$status}_" . now()->format('Y-m-d') . ".csv";
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];
        $callback = function () use ($employees) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code','First Name','Last Name','Gender','Email','Phone','Department','Position','Employment Type','Status','Salary','Currency','Hire Date']);
            foreach ($employees as $emp) {
                $ed  = $emp->employmentDetails;
                $sal = $emp->currentSalary;
                fputcsv($handle, [
                    $emp->employee_code,
                    $emp->first_name,
                    $emp->last_name,
                    $emp->gender ?? '',
                    $emp->email  ?? '',
                    $emp->phone  ?? '',
                    $ed?->department?->name ?? '',
                    $ed?->position?->name   ?? '',
                    $ed ? $ed->employmentTypeLabel() : '',
                    $emp->status,
                    $sal ? $sal->amount : '',
                    $sal ? $sal->currency : '',
                    $ed?->hire_date ?? '',
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    // ── EMPLOYEE CREATE ──────────────────────────────────────────────────────

    public function createEmployee()
    {
        $departments = Department::orderBy('name')->get();
        $positions   = Position::orderBy('name')->get();
        $shifts      = Shift::orderBy('name')->get();
        $managers    = Employee::where('status','active')->orderBy('first_name')->get();
        return view('pages.hr.employee_create', compact('departments','positions','shifts','managers'));
    }

    public function storeEmployee(Request $req)
    {
        $req->validate([
            'first_name'      => 'required|string|max:80',
            'last_name'       => 'required|string|max:80',
            'gender'          => 'nullable|in:male,female',
            'date_of_birth'   => 'nullable|date',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:100',
            'address'         => 'nullable|string|max:255',
            'national_id'     => 'nullable|string|max:50',
            'tin_number'      => 'nullable|string|max:30',
            'pension_number'  => 'nullable|string|max:30',
            'department_id'   => 'nullable|exists:departments,id',
            'position_id'     => 'nullable|exists:positions,id',
            'employment_type' => 'nullable|in:full_time,part_time,contract,intern',
            'hire_date'       => 'nullable|date',
            'currency'        => 'nullable|string|max:10',
            'salary'          => 'nullable|numeric|min:0',
            'bank_name'       => 'nullable|string|max:100',
            'bank_account_no' => 'nullable|string|max:50',
            'hr_notes'        => 'nullable|string|max:1000',
        ]);
        $employee = $this->profileService->create($req->all());
        AuditLog::log('created','hr',"Employee created: {$employee->employee_code}");
        return redirect()->route('hr.show', $employee->id)
            ->with('flash_success', "Employee {$employee->full_name} created.");
    }

    // ── USER ↔ EMPLOYEE LINKING ──────────────────────────────────────────────

    /**
     * Show staff users that have no linked Employee record.
     */
    public function unlinkedUsers()
    {
        $staffTypes = ['teacher', 'hr_manager', 'admin', 'super_admin', 'employee'];
        $linkedUserIds = Employee::whereNotNull('user_id')->pluck('user_id');

        $unlinked = User::whereIn('user_type', $staffTypes)
            ->whereNotIn('id', $linkedUserIds)
            ->orderBy('name')->get();

        $employees = Employee::whereNull('user_id')
            ->with('employmentDetails.department')
            ->orderBy('first_name')->get();

        // Get ALL staff users for linking (including those already linked, so they can be re-linked)
        $availableUsers = User::whereIn('user_type', $staffTypes)
            ->orderBy('name')->get();

        return view('pages.hr.employees_unlinked', compact('unlinked', 'employees', 'availableUsers'));
    }

    /**
     * Auto-create an Employee record from an existing unlinked User.
     */
    public function syncFromUser($userId)
    {
        $user = User::findOrFail($userId);

        if (Employee::where('user_id', $userId)->exists()) {
            return back()->with('flash_danger', "{$user->name} already has an Employee record.");
        }

        $employee = EmployeeProfileService::createFromUser($user);
        AuditLog::log('created', 'hr', "Employee synced from user #{$userId} ({$user->name})");

        return back()->with('flash_success',
            "Employee record {$employee->employee_code} created for {$user->name}.");
    }

    /**
     * Auto-create Employee records for ALL unlinked staff users at once.
     */
    public function syncAllUsers()
    {
        $staffTypes  = ['teacher', 'hr_manager', 'admin', 'super_admin'];
        $linkedIds   = Employee::whereNotNull('user_id')->pluck('user_id');
        $unlinked    = User::whereIn('user_type', $staffTypes)
            ->whereNotIn('id', $linkedIds)->get();

        $created = 0;
        foreach ($unlinked as $user) {
            if (EmployeeProfileService::createFromUser($user)) {
                $created++;
            }
        }

        AuditLog::log('created', 'hr', "Bulk employee sync: {$created} records created");
        return back()->with('flash_success', "{$created} Employee record(s) created successfully.");
    }

    /**
     * Link an existing Employee record to an existing User account.
     */
    public function linkUser(Request $req, $hrId)
    {
        $req->validate(['user_id' => 'required|exists:users,id']);
        $employee = Employee::findOrFail($hrId);

        if ($employee->user_id) {
            return back()->with('flash_danger', 'This employee is already linked to a user account.');
        }
        if (Employee::where('user_id', $req->user_id)->exists()) {
            return back()->with('flash_danger', 'That user account is already linked to another employee.');
        }

        $employee->update(['user_id' => $req->user_id]);
        AuditLog::log('updated', 'hr', "Employee #{$hrId} linked to user #{$req->user_id}");

        return back()->with('flash_success', 'User account linked to employee.');
    }

    /**
     * Unlink a User account from an Employee record.
     */
    public function unlinkUser($hrId)
    {
        $employee = Employee::findOrFail($hrId);
        $employee->update(['user_id' => null]);
        AuditLog::log('updated', 'hr', "Employee #{$hrId} unlinked from user account");

        return back()->with('flash_success', 'User account unlinked.');
    }

    // ── EMPLOYEE PROFILE — VIEW ──────────────────────────────────────────────

    public function show($hrId)
    {
        $employee = Employee::with([
            'employmentDetails.department','employmentDetails.position',
            'employmentDetails.reportingManager','emergencyContacts','qualifications',
            'currentSalary','currentPosition.position','currentShift.shift','user',
        ])->findOrFail($hrId);

        $subjects     = $employee->user_id ? Subject::where('teacher_id',$employee->user_id)->with('my_class')->get() : collect();
        $attendance   = StaffAttendance::where('employee_id',$hrId)->orderByDesc('date')->take(30)->get();
        $recentRate   = $this->attendanceService->recentRate($hrId, 30);
        $presentCount = $recentRate['present'];
        $totalCount   = $recentRate['total'];
        $attPct       = $recentRate['rate'];
        $positions    = Position::orderBy('name')->get();
        $shifts       = Shift::orderBy('name')->get();
        $payrolls     = StaffPayroll::where('employee_id',$hrId)->orderByDesc('month')->take(6)->get();

        return view('pages.hr.show', compact(
            'employee','subjects','attendance','attPct','presentCount','totalCount','positions','shifts','payrolls'
        ));
    }

    // ── EMPLOYEE PROFILE — EDIT / UPDATE ────────────────────────────────────

    public function editProfile($hrId)
    {
        $employee    = Employee::with('employmentDetails','emergencyContacts','qualifications')->findOrFail($hrId);
        $departments = Department::orderBy('name')->get();
        $positions   = Position::orderBy('name')->get();
        $managers    = Employee::where('status','active')->where('id','!=',$hrId)->orderBy('first_name')->get();
        return view('pages.hr.profile_edit', compact('employee','departments','positions','managers'));
    }

    public function updateProfile(Request $req, $hrId)
    {
        $req->validate([
            'first_name'           => 'required|string|max:80',
            'last_name'            => 'required|string|max:80',
            'gender'               => 'nullable|in:male,female',
            'date_of_birth'        => 'nullable|date',
            'phone'                => 'nullable|string|max:20',
            'phone2'               => 'nullable|string|max:20',
            'email'                => 'nullable|email|max:100',
            'address'              => 'nullable|string|max:255',
            'national_id'          => 'nullable|string|max:50',
            'tin_number'           => 'nullable|string|max:30',
            'pension_number'       => 'nullable|string|max:30',
            'hr_notes'             => 'nullable|string|max:1000',
            'department_id'        => 'nullable|exists:departments,id',
            'position_id'          => 'nullable|exists:positions,id',
            'reporting_manager_id' => 'nullable|exists:employees,id',
            'employment_type'      => 'nullable|in:full_time,part_time,contract,intern',
            'hire_date'            => 'nullable|date',
            'contract_end_date'    => 'nullable|date',
            'currency'             => 'nullable|string|max:10',
            'salary'               => 'nullable|numeric|min:0',
            'is_remote'            => 'nullable|boolean',
            'bank_name'            => 'nullable|string|max:100',
            'bank_account_no'      => 'nullable|string|max:50',
            'emergency.*.name'     => 'nullable|string|max:100',
            'emergency.*.phone'    => 'nullable|string|max:20',
            'emergency.*.relationship' => 'nullable|string|max:50',
            'qualifications.*.degree'           => 'nullable|string|max:100',
            'qualifications.*.field_of_study'  => 'nullable|string|max:100',
            'qualifications.*.institution'     => 'nullable|string|max:100',
            'qualifications.*.graduation_year' => 'nullable|integer|min:1950|max:' . date('Y'),
            'qualifications.*.certificate'     => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);
        $employee = Employee::findOrFail($hrId);
        $this->profileService->update($employee, $req->all());
        if ($req->has('emergency')) {
            $this->profileService->syncEmergencyContacts($employee, $req->emergency);
        }
        
        // Handle qualifications with file uploads
        if ($req->has('qualifications')) {
            $this->updateQualifications($employee, $req->qualifications, $req);
        }
        
        return back()->with('flash_success','Employee profile updated.');
    }

    /**
     * Update employee qualifications with file uploads
     */
    private function updateQualifications($employee, $qualifications, $req)
    {
        foreach ($qualifications as $index => $qual) {
            // Skip empty rows
            if (empty($qual['degree']) && empty($qual['field_of_study']) && empty($qual['institution'])) {
                continue;
            }

            $qualData = [
                'degree'           => $qual['degree'] ?? null,
                'field_of_study'   => $qual['field_of_study'] ?? null,
                'institution'      => $qual['institution'] ?? null,
                'graduation_year'  => $qual['graduation_year'] ?? null,
            ];

            // Handle file upload
            if ($req->hasFile("qualifications.{$index}.certificate")) {
                $file = $req->file("qualifications.{$index}.certificate");
                $path = $file->store('qualifications/' . $employee->id, 'public');
                $qualData['certificate_path'] = $path;
            }

            // Update or create qualification
            if (!empty($qual['id'])) {
                // Update existing - only update provided fields
                $existingQual = EmployeeQualification::where('id', $qual['id'])
                    ->where('employee_id', $employee->id)
                    ->first();
                
                if ($existingQual) {
                    // If no new file uploaded, keep the existing certificate_path
                    if (!$req->hasFile("qualifications.{$index}.certificate")) {
                        unset($qualData['certificate_path']);
                    }
                    $existingQual->update($qualData);
                }
            } else {
                // Create new
                $qualData['employee_id'] = $employee->id;
                EmployeeQualification::create($qualData);
            }
        }
    }

    // ── EMPLOYEE STATUS ──────────────────────────────────────────────────────

    public function terminateEmployee(Request $req, $hrId)
    {
        $req->validate(['termination_date'=>'required|date','termination_reason'=>'required|string|max:500']);
        $this->profileService->terminate(Employee::findOrFail($hrId), $req->termination_date, $req->termination_reason);
        return back()->with('flash_success','Employee terminated.');
    }

    public function reactivateEmployee($hrId)
    {
        $this->profileService->reactivate(Employee::findOrFail($hrId));
        return back()->with('flash_success','Employee reactivated.');
    }

    public function changeEmployeeStatus(Request $req, $hrId)
    {
        $req->validate(['status'=>'required|in:active,on_leave,suspended']);
        $this->profileService->changeStatus(Employee::findOrFail($hrId), $req->status);
        return back()->with('flash_success','Status updated.');
    }

    // ── QUALIFICATIONS ───────────────────────────────────────────────────────

    public function addQualification(Request $req, $hrId)
    {
        $req->validate([
            'degree'          => 'required|string|max:100',
            'field_of_study'  => 'nullable|string|max:150',
            'institution'     => 'nullable|string|max:150',
            'graduation_year' => 'nullable|integer|min:1950|max:'.date('Y'),
        ]);
        $this->profileService->addQualification(Employee::findOrFail($hrId), $req->all());
        return back()->with('flash_success','Qualification added.');
    }

    public function deleteQualification(Request $req, $hrId)
    {
        $req->validate(['qualification_id'=>'required|exists:employee_qualifications,id']);
        $this->profileService->deleteQualification($req->qualification_id, Employee::findOrFail($hrId));
        return back()->with('flash_success','Qualification removed.');
    }

    // ── DEPARTMENTS ──────────────────────────────────────────────────────────

    public function departments(Request $req)
    {
        $search      = trim($req->get('search', ''));
        $departments = Department::with('positions')
            ->withCount(['employees as employee_count'])
            ->when($search, fn($q) => $q->where('name','like',"%{$search}%")
                                        ->orWhere('description','like',"%{$search}%"))
            ->orderBy('name')->get();

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.exports.departments_pdf', compact('departments','search'));
            return $pdf->download('departments.pdf');
        }
        if ($req->get('export') === 'csv') {
            return $this->exportDepartmentsCsv($departments);
        }

        return view('pages.hr.departments', compact('departments','search'));
    }

    protected function exportDepartmentsCsv($departments)
    {
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename=departments_'.now()->format('Y-m-d').'.csv'];
        $callback = function () use ($departments) {
            $h = fopen('php://output','w');
            fputcsv($h, ['Name','Description','Employees','Positions']);
            foreach ($departments as $d) {
                fputcsv($h, [$d->name, $d->description ?? '', $d->employee_count, $d->positions->count()]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function storeDepartment(Request $req)
    {
        $req->validate(['name'=>'required|string|max:100|unique:departments,name']);
        $dept = Department::create($req->only('name','description'));
        AuditLog::log('created','hr',"Department '{$dept->name}' created");
        return response()->json(['ok'=>true,'msg'=>'Department created.','id'=>$dept->id,'name'=>$dept->name]);
    }

    public function updateDepartment(Request $req, $hrId)
    {
        $req->validate(['name'=>'required|string|max:100|unique:departments,name,'.$hrId]);
        $dept = Department::findOrFail($hrId);
        $dept->update($req->only('name','description'));
        AuditLog::log('updated','hr',"Department '{$dept->name}' updated");
        return Qs::jsonUpdateOk();
    }

    public function destroyDepartment($hrId)
    {
        $dept = Department::findOrFail($hrId);
        EmploymentDetails::where('department_id',$hrId)->update(['department_id'=>null]);
        Position::where('department_id',$hrId)->update(['department_id'=>null]);
        $dept->delete();
        AuditLog::log('deleted','hr',"Department ID {$hrId} deleted");
        return back()->with('flash_success','Department deleted.');
    }

    // ── POSITIONS ────────────────────────────────────────────────────────────

    public function positions(Request $req)
    {
        $search    = trim($req->get('search', ''));
        $deptFilter = $req->get('department_id');
        $positions = Position::with('department')
            ->withCount(['employees as employee_count'])
            ->when($search, fn($q) => $q->where('name','like',"%{$search}%")
                                        ->orWhere('description','like',"%{$search}%"))
            ->when($deptFilter, fn($q) => $q->where('department_id', $deptFilter))
            ->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.exports.positions_pdf', compact('positions','search'));
            return $pdf->download('positions.pdf');
        }
        if ($req->get('export') === 'csv') {
            return $this->exportPositionsCsv($positions);
        }

        return view('pages.hr.positions', compact('positions','departments','search','deptFilter'));
    }

    protected function exportPositionsCsv($positions)
    {
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename=positions_'.now()->format('Y-m-d').'.csv'];
        $callback = function () use ($positions) {
            $h = fopen('php://output','w');
            fputcsv($h, ['Name','Department','Description','Employees']);
            foreach ($positions as $p) {
                fputcsv($h, [$p->name, $p->department?->name ?? 'All Departments', $p->description ?? '', $p->employee_count]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function storePosition(Request $req)
    {
        $req->validate(['name'=>'required|string|max:100|unique:positions,name','department_id'=>'nullable|exists:departments,id','description'=>'nullable|string|max:255']);
        $pos = Position::create($req->only('name','department_id','description'));
        AuditLog::log('created','hr',"Position '{$pos->name}' created");
        return response()->json(['ok'=>true,'msg'=>'Position created.','id'=>$pos->id,'name'=>$pos->name,'dept'=>$pos->department?->name ?? '—']);
    }

    public function updatePosition(Request $req, $hrId)
    {
        $req->validate(['name'=>'required|string|max:100|unique:positions,name,'.$hrId,'department_id'=>'nullable|exists:departments,id']);
        Position::findOrFail($hrId)->update($req->only('name','department_id','description'));
        AuditLog::log('updated','hr',"Position updated ID {$hrId}");
        return Qs::jsonUpdateOk();
    }

    public function destroyPosition($hrId)
    {
        EmploymentDetails::where('position_id',$hrId)->update(['position_id'=>null]);
        Position::findOrFail($hrId)->delete();
        AuditLog::log('deleted','hr',"Position ID {$hrId} deleted");
        return back()->with('flash_success','Position deleted.');
    }

    public function positionsByDepartment($departmentId)
    {
        return response()->json(
            Position::where('department_id',$departmentId)->orWhereNull('department_id')->orderBy('name')->get(['id','name'])
        );
    }

    // ── SHIFTS ───────────────────────────────────────────────────────────────

    public function shifts()
    {
        return view('pages.hr.shifts', ['shifts' => Shift::withCount('staffShifts')->orderBy('name')->get()]);
    }

    public function storeShift(Request $req)
    {
        $req->validate(['name'=>'required|string|max:100|unique:shifts,name','start_time'=>'required','end_time'=>'required']);
        Shift::create($req->only('name','start_time','end_time','description'));
        AuditLog::log('created','hr',"Shift '{$req->name}' created");
        return Qs::jsonStoreOk();
    }

    public function updateShift(Request $req, $hrId)
    {
        $req->validate(['name'=>'required|string|max:100|unique:shifts,name,'.$hrId,'start_time'=>'required','end_time'=>'required']);
        Shift::findOrFail($hrId)->update($req->only('name','start_time','end_time','description'));
        AuditLog::log('updated','hr',"Shift updated ID {$hrId}");
        return Qs::jsonUpdateOk();
    }

    public function destroyShift($hrId)
    {
        Shift::findOrFail($hrId)->delete();
        AuditLog::log('deleted','hr',"Shift ID {$hrId} deleted");
        return back()->with('flash_success','Shift deleted.');
    }

    public function assignShift(Request $req, $hrId)
    {
        $req->validate(['shift_id'=>'required|exists:shifts,id','start_date'=>'required|date']);
        StaffShift::where('employee_id',$hrId)->whereNull('end_date')
            ->update(['end_date'=>Carbon::parse($req->start_date)->subDay()->toDateString()]);
        StaffShift::create(['employee_id'=>$hrId,'shift_id'=>$req->shift_id,'start_date'=>$req->start_date,'end_date'=>null]);
        AuditLog::log('updated','hr',"Shift assigned to employee ID {$hrId}");
        return back()->with('flash_success','Shift assigned.');
    }

    // ── SALARIES ─────────────────────────────────────────────────────────────

    public function assignSalary(Request $req, $hrId)
    {
        $req->validate(['amount'=>'required|numeric|min:0','currency'=>'required|string|max:10','start_date'=>'required|date']);
        StaffSalary::where('employee_id',$hrId)->whereNull('end_date')
            ->update(['end_date'=>Carbon::parse($req->start_date)->subDay()->toDateString()]);
        StaffSalary::create(['employee_id'=>$hrId,'currency'=>$req->currency,'amount'=>$req->amount,'start_date'=>$req->start_date,'end_date'=>null,'notes'=>$req->notes]);
        AuditLog::log('updated','hr',"Salary updated for employee ID {$hrId}");
        return back()->with('flash_success','Salary updated.');
    }

    // ── ATTENDANCE ───────────────────────────────────────────────────────────

    public function attendance(Request $req)
    {
        $month          = $req->get('month', now()->format('Y-m'));
        $search         = trim($req->get('search', ''));
        $employees      = Employee::where('status','active')
            ->with(['employmentDetails.department','currentShift.shift'])
            ->when($search, fn($q) => $q->where(fn($i) =>
                $i->where('first_name','like',"%{$search}%")
                  ->orWhere('last_name','like',"%{$search}%")
                  ->orWhere('employee_code','like',"%{$search}%")
            ))
            ->orderBy('first_name')->get();
        $today          = now()->toDateString();
        $todayRecords   = StaffAttendance::where('date',$today)->whereNotNull('employee_id')->get()->keyBy('employee_id');
        $monthlySummary = $this->attendanceService->allEmployeesMonthlySummary($month);

        // Holiday info for today and the selected month
        $todayHoliday    = $this->holidayService->getHolidayName($today);
        $monthHolidays   = EthiopianHoliday::where('year', substr($month, 0, 4))
            ->where('date', 'like', $month . '%')
            ->orderBy('date')->get();

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.exports.attendance_summary_pdf', compact('employees','monthlySummary','month','search'));
            return $pdf->setPaper('a4','landscape')->download("attendance_summary_{$month}.pdf");
        }
        if ($req->get('export') === 'csv') {
            return $this->exportAttendanceSummaryCsv($employees, $monthlySummary, $month);
        }

        return view('pages.hr.attendance', compact('employees','today','todayRecords','month','monthlySummary','search','todayHoliday','monthHolidays'));
    }

    protected function exportAttendanceSummaryCsv($employees, $monthlySummary, $month)
    {
        $filename = "attendance_summary_{$month}.csv";
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>"attachment; filename={$filename}"];
        $callback = function () use ($employees, $monthlySummary, $month) {
            $h = fopen('php://output','w');
            fputcsv($h, ["Attendance Summary — {$month}"]);
            fputcsv($h, []);
            fputcsv($h, ['Employee','Code','Department','Present','Late','Absent','Leave','Rate %','Hours Worked','Overtime (h)']);
            foreach ($employees as $emp) {
                $s = $monthlySummary->get($emp->id);
                if (!$s) continue;
                fputcsv($h, [
                    $emp->full_name,
                    $emp->employee_code,
                    $emp->employmentDetails?->department?->name ?? '',
                    $s['present'], $s['late'], $s['absent'], $s['leave'],
                    $s['attendance_rate'],
                    $s['actual_hours'] ?? 0,
                    $s['overtime_hours'] ?? 0,
                ]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import attendance from a CSV file.
     * CSV format: employee_code, date, status, sign_in_time, sign_off_time, leave_type, remark
     */
    public function importAttendanceCsv(Request $req)
    {
        $req->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file    = $req->file('csv_file');
        $handle  = fopen($file->getRealPath(), 'r');
        $header  = fgetcsv($handle); // skip header row

        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $row      = 1;

        while (($line = fgetcsv($handle)) !== false) {
            $row++;
            if (count($line) < 3) { $skipped++; continue; }

            [$code, $date, $status] = $line;
            $signIn    = trim($line[3] ?? '');
            $signOff   = trim($line[4] ?? '');
            $leaveType = trim($line[5] ?? '');
            $remark    = trim($line[6] ?? '');

            $code   = trim($code);
            $date   = trim($date);
            $status = strtolower(trim($status));

            // Validate status
            if (!in_array($status, ['present','absent','late','leave'])) {
                $errors[] = "Row {$row}: Invalid status '{$status}' for code '{$code}'.";
                $skipped++;
                continue;
            }

            // Find employee by code
            $employee = \App\Models\Employee::where('employee_code', $code)->first();
            if (!$employee) {
                $errors[] = "Row {$row}: Employee code '{$code}' not found.";
                $skipped++;
                continue;
            }

            // Validate date
            try {
                \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: Invalid date '{$date}'.";
                $skipped++;
                continue;
            }

            $this->attendanceService->save($employee->id, $date, [
                'status'        => $status,
                'sign_in_time'  => $signIn  ?: null,
                'sign_off_time' => $signOff ?: null,
                'leave_type'    => $leaveType ?: null,
                'remark'        => $remark ?: null,
            ]);
            $imported++;
        }

        fclose($handle);

        AuditLog::log('created', 'hr', "Attendance CSV imported: {$imported} records, {$skipped} skipped");

        $msg = "Import complete: {$imported} record(s) imported.";
        if ($skipped > 0) $msg .= " {$skipped} row(s) skipped.";

        $flash = $skipped > 0 ? 'flash_danger' : 'flash_success';
        return back()
            ->with($flash, $msg)
            ->with('import_errors', $errors);
    }

    /**
     * Download a blank CSV template for attendance import.
     */
    public function downloadAttendanceTemplate()
    {
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename=attendance_import_template.csv'];
        $callback = function () {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['employee_code', 'date', 'status', 'sign_in_time', 'sign_off_time', 'leave_type', 'remark']);
            // Example rows
            fputcsv($h, ['STF-0001', date('Y-m-d'), 'present', '08:00', '17:00', '', '']);
            fputcsv($h, ['STF-0002', date('Y-m-d'), 'absent',  '',      '',      '', 'Sick']);
            fputcsv($h, ['STF-0003', date('Y-m-d'), 'late',    '08:30', '17:00', '', '']);
            fputcsv($h, ['STF-0004', date('Y-m-d'), 'leave',   '',      '',      'annual', 'Annual leave']);
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function saveAttendance(Request $req)
    {
        $req->validate([
            'date'                       => 'required|date',
            'attendance'                 => 'required|array',
            'attendance.*.status'        => 'required|in:present,absent,late,leave',
            'attendance.*.sign_in_time'  => 'nullable|date_format:H:i',
            'attendance.*.sign_off_time' => 'nullable|date_format:H:i',
            'attendance.*.leave_type'    => 'nullable|in:annual,sick,maternity,paternity,unpaid,other',
        ]);
        $count = $this->attendanceService->saveBulk($req->date, $req->attendance, $req->input('remark',[]));
        AuditLog::log('created','hr',"Attendance saved for {$req->date} — {$count} records");
        return back()->with('flash_success',"Attendance saved. {$count} records updated.");
    }

    public function attendanceReport($hrId)
    {
        $employee        = Employee::findOrFail($hrId);
        $month           = request('month', now()->format('Y-m'));
        $records         = StaffAttendance::where('employee_id',$hrId)->when(request('month'),fn($q)=>$q->where('date','like',$month.'%'))->orderByDesc('date')->paginate(31);
        $summary         = $this->attendanceService->monthlySummary($hrId, $month);
        $availableMonths = StaffAttendance::where('employee_id',$hrId)->selectRaw("DATE_FORMAT(date,'%Y-%m') as month")->groupBy('month')->orderByDesc('month')->pluck('month');

        // ── Export ───────────────────────────────────────────────────────────
        if (request('export') === 'pdf') {
            $allRecords = StaffAttendance::where('employee_id',$hrId)->where('date','like',$month.'%')->orderBy('date')->get();
            $pdf = PDF::loadView('pages.hr.exports.attendance_pdf', compact('employee','allRecords','summary','month'));
            return $pdf->download("attendance_{$employee->employee_code}_{$month}.pdf");
        }

        if (request('export') === 'csv') {
            $allRecords = StaffAttendance::where('employee_id',$hrId)->where('date','like',$month.'%')->orderBy('date')->get();
            return $this->exportAttendanceCsv($employee, $allRecords, $month);
        }

        return view('pages.hr.attendance_report', compact('employee','records','summary','month','availableMonths'));
    }

    protected function exportAttendanceCsv($employee, $records, $month)
    {
        $filename = "attendance_{$employee->employee_code}_{$month}.csv";
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];
        $callback = function () use ($employee, $records, $month) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ["Attendance Report — {$employee->full_name} ({$employee->employee_code})"]);
            fputcsv($handle, ["Month: {$month}"]);
            fputcsv($handle, []);
            fputcsv($handle, ['Date','Status','Leave Type','Sign In','Sign Off','Hours Worked','Overtime (h)','Late (min)','Remark']);
            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->date,
                    ucfirst($r->status),
                    ($r->status === 'leave' && $r->leave_type) ? $r->leaveTypeLabel() : '',
                    $r->sign_in_time  ?? '',
                    $r->sign_off_time ?? '',
                    $r->actual_hours  ?? '',
                    $r->overtime_hours > 0 ? $r->overtime_hours : '',
                    $r->late_minutes  > 0 ? $r->late_minutes  : '',
                    $r->remark        ?? '',
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    // ── PAYROLL ──────────────────────────────────────────────────────────────

    public function payroll(Request $req)
    {
        $month        = $req->get('month', now()->format('Y-m'));
        $status       = $req->get('status','all');
        $employees    = Employee::where('status','active')->with(['employmentDetails.position','employmentDetails.department'])->orderBy('first_name')->get();
        $payrolls     = StaffPayroll::where('month',$month)->when($status!=='all',fn($q)=>$q->where('status',$status))->with('employee')->get()->keyBy('employee_id');
        $statusCounts = array_merge(['draft'=>0,'approved'=>0,'paid'=>0], StaffPayroll::where('month',$month)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status')->toArray());

        // ── Export ───────────────────────────────────────────────────────────
        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.exports.payroll_pdf', compact('employees','payrolls','month','status','statusCounts'));
            return $pdf->setPaper('a4','landscape')->download("payroll_{$month}.pdf");
        }

        if ($req->get('export') === 'csv') {
            return $this->exportPayrollCsv($employees, $payrolls, $month);
        }

        return view('pages.hr.payroll', compact('employees','month','payrolls','status','statusCounts'));
    }

    protected function exportPayrollCsv($employees, $payrolls, $month)
    {
        $filename = "payroll_{$month}.csv";
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];
        $callback = function () use ($employees, $payrolls, $month) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ["Payroll Report — {$month}"]);
            fputcsv($handle, []);
            fputcsv($handle, ['Employee','Code','Department','Position','Base Salary','Present Days','Absent Days','Earnings','Deductions','Net Pay','Currency','Status']);
            foreach ($employees as $emp) {
                $pr = $payrolls->get($emp->id);
                $ed = $emp->employmentDetails;
                if (!$pr) continue;
                fputcsv($handle, [
                    $emp->full_name,
                    $emp->employee_code,
                    $ed?->department?->name ?? '',
                    $ed?->position?->name   ?? '',
                    $pr->base_salary,
                    $pr->present_days,
                    $pr->absent_days,
                    $pr->base_salary + $pr->allowances,
                    $pr->deductions,
                    $pr->net_pay,
                    $pr->currency,
                    ucfirst($pr->status),
                ]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function generatePayroll(Request $req)
    {
        $req->validate(['month'=>'required|date_format:Y-m']);
        $result = $this->payrollService->generateBulk($req->month, $this->attendanceService);
        AuditLog::log('created','hr',"Payroll generated for {$req->month}");
        return back()->with('flash_success',"Payroll generated. {$result['generated']} created, {$result['skipped']} skipped.");
    }

    public function editPayroll($hrId)
    {
        $payroll = StaffPayroll::with(['employee','items'])->findOrFail($hrId);
        return view('pages.hr.payroll_edit', compact('payroll'));
    }

    public function updatePayroll(Request $req, $hrId)
    {
        $req->validate(['base_salary'=>'required|numeric|min:0','notes'=>'nullable|string|max:500']);
        $payroll = StaffPayroll::findOrFail($hrId);
        if (!$payroll->isDraft()) return back()->with('flash_danger','Only draft payrolls can be edited.');
        $payroll->update(['base_salary'=>$req->base_salary,'notes'=>$req->notes]);
        $this->payrollService->recalculateFromItems($payroll);
        AuditLog::log('updated','hr',"Payroll #{$hrId} updated");
        return back()->with('flash_success','Payroll updated.');
    }

    public function addPayrollItem(Request $req, $hrId)
    {
        $req->validate(['type'=>'required|in:earning,deduction','label'=>'required|string|max:100','amount'=>'required|numeric|min:0','note'=>'nullable|string|max:255']);
        $this->payrollService->addItem(StaffPayroll::findOrFail($hrId), $req->type, $req->label, $req->amount, $req->note);
        return back()->with('flash_success','Item added.');
    }

    public function removePayrollItem(Request $req, $hrId)
    {
        $req->validate(['item_id'=>'required|exists:payroll_items,id']);
        $this->payrollService->removeItem(StaffPayroll::findOrFail($hrId), $req->item_id);
        return back()->with('flash_success','Item removed.');
    }

    public function approvePayroll($hrId)
    {
        $this->payrollService->approve(StaffPayroll::findOrFail($hrId), auth()->id());
        return back()->with('flash_success','Payroll approved.');
    }

    public function markPayrollPaid($hrId)
    {
        $this->payrollService->markPaid(StaffPayroll::findOrFail($hrId), auth()->id());
        return back()->with('flash_success','Payroll marked as paid.');
    }

    public function revertPayrollToDraft($hrId)
    {
        $this->payrollService->revertToDraft(StaffPayroll::findOrFail($hrId));
        return back()->with('flash_success','Payroll reverted to draft.');
    }

    // ── CONTRACTS ────────────────────────────────────────────────────────────

    public function contracts(Request $req)
    {
        $filter = $req->get('filter', 'expiring'); // expiring, expired, all, permanent
        $days   = (int) $req->get('days', 60);

        $query = EmploymentDetails::with(['employee.employmentDetails'])
            ->whereHas('employee', fn($q) => $q->where('status', 'active'))
            ->whereNotNull('contract_end_date');

        $contracts = match($filter) {
            'expired'   => $query->where('contract_end_date', '<', now())->get(),
            'expiring'  => $query->whereBetween('contract_end_date', [now(), now()->addDays($days)])->get(),
            'permanent' => EmploymentDetails::with('employee')
                ->whereHas('employee', fn($q) => $q->where('status', 'active'))
                ->whereNull('contract_end_date')->get(),
            default     => EmploymentDetails::with('employee')
                ->whereHas('employee', fn($q) => $q->where('status', 'active'))
                ->get(),
        };

        // Summary counts
        $expiredCount  = EmploymentDetails::whereHas('employee', fn($q) => $q->where('status','active'))
            ->whereNotNull('contract_end_date')
            ->where('contract_end_date', '<', now())->count();
        $expiringCount = EmploymentDetails::whereHas('employee', fn($q) => $q->where('status','active'))
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [now(), now()->addDays(60)])->count();
        $permanentCount = EmploymentDetails::whereHas('employee', fn($q) => $q->where('status','active'))
            ->whereNull('contract_end_date')->count();

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.contracts_pdf', compact('contracts','filter','days'));
            return $pdf->download("contracts_{$filter}.pdf");
        }
        if ($req->get('export') === 'csv') {
            return $this->exportContractsCsv($contracts, $filter);
        }

        return view('pages.hr.contracts', compact(
            'contracts','filter','days','expiredCount','expiringCount','permanentCount'
        ));
    }

    protected function exportContractsCsv($contracts, $filter)
    {
        $filename = "contracts_{$filter}_".now()->format('Y-m-d').".csv";
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>"attachment; filename={$filename}"];
        $callback = function () use ($contracts) {
            $h = fopen('php://output','w');
            fputcsv($h, ['Employee','Code','Department','Employment Type','Hire Date','Contract End Date','Days Until Expiry','Status']);
            foreach ($contracts as $ed) {
                $emp = $ed->employee;
                if (!$emp) continue;
                fputcsv($h, [
                    $emp->full_name,
                    $emp->employee_code,
                    $ed->department?->name ?? '—',
                    $ed->employmentTypeLabel(),
                    $ed->hire_date?->format('Y-m-d') ?? '—',
                    $ed->contract_end_date?->format('Y-m-d') ?? 'Permanent',
                    $ed->contract_end_date ? $ed->daysUntilExpiry() : '—',
                    $ed->contractStatusLabel(),
                ]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function renewContract(Request $req, $hrId)
    {
        // Validate with max date (10 years from now)
        $maxDate = now()->addYears(10)->format('Y-m-d');
        $req->validate([
            'contract_end_date' => 'required|date|after:today|before:' . $maxDate,
            'notes'             => 'nullable|string|max:500',
        ]);

        $employee = Employee::findOrFail($hrId);
        $ed       = $employee->employmentDetails;

        if (!$ed) {
            return back()->with('flash_danger', 'No employment details found for this employee.');
        }

        $oldDate = $ed->contract_end_date?->format('d M Y') ?? 'none';
        $newDate = Carbon::parse($req->contract_end_date)->format('d M Y');
        $ed->update(['contract_end_date' => $req->contract_end_date]);

        AuditLog::log('updated', 'hr',
            "Contract renewed for {$employee->employee_code}: {$oldDate} → {$newDate}. ".($req->notes ?? '')
        );

        return back()->with('flash_success',
            "Contract renewed for {$employee->full_name} until {$newDate}.");
    }

    // ── WORKLOAD ─────────────────────────────────────────────────────────────

    public function workload()
    {
        $teachers = User::where('user_type','teacher')->orderBy('name')->get()->map(function($t) {
            $t->subjects = Subject::where('teacher_id',$t->id)->with('my_class')->get();
            return $t;
        });
        return view('pages.hr.workload', compact('teachers'));
    }

    // ── ETHIOPIAN HOLIDAYS ────────────────────────────────────────────────────

    public function holidays(Request $req)
    {
        $year     = (int) $req->get('year', now()->year);
        $holidays = EthiopianHoliday::where('year', $year)->orderBy('date')->get();
        $preview  = $this->holidayService->getHolidaysForYear($year);
        return view('pages.hr.holidays', compact('holidays', 'year', 'preview'));
    }

    public function storeHoliday(Request $req)
    {
        $req->validate([
            'date'  => 'required|date',
            'name'  => 'required|string|max:150',
            'type'  => 'required|in:public,religious,school',
            'notes' => 'nullable|string|max:255',
        ]);
        $year = Carbon::parse($req->date)->year;
        EthiopianHoliday::updateOrCreate(
            ['date' => $req->date, 'name' => $req->name],
            ['type' => $req->type, 'is_paid' => true, 'year' => $year, 'notes' => $req->notes]
        );
        AuditLog::log('created', 'hr', "Holiday added: {$req->name} on {$req->date}");
        return back()->with('flash_success', "Holiday '{$req->name}' added.");
    }

    public function seedHolidays(Request $req)
    {
        $req->validate(['year' => 'required|integer|min:2020|max:2099']);
        $count = $this->holidayService->seedYear((int) $req->year);
        AuditLog::log('created', 'hr', "Ethiopian holidays seeded for {$req->year}: {$count} records");
        return back()->with('flash_success', "{$count} holidays seeded for {$req->year}.");
    }

    public function destroyHoliday($hrId)
    {
        $holiday = EthiopianHoliday::findOrFail($hrId);
        AuditLog::log('deleted', 'hr', "Holiday deleted: {$holiday->name} on {$holiday->date}");
        $holiday->delete();
        return back()->with('flash_success', 'Holiday removed.');
    }
}
