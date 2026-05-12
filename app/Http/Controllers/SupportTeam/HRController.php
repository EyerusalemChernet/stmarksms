<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentDetails;
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

    public function __construct(
        EmployeeProfileService $profileService,
        AttendanceService $attendanceService,
        PayrollService $payrollService
    ) {
        $this->middleware('hr_manager');
        $this->profileService    = $profileService;
        $this->attendanceService = $attendanceService;
        $this->payrollService    = $payrollService;
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
        ]);
        $employee = Employee::findOrFail($hrId);
        $this->profileService->update($employee, $req->all());
        if ($req->has('emergency')) {
            $this->profileService->syncEmergencyContacts($employee, $req->emergency);
        }
        return back()->with('flash_success','Employee profile updated.');
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

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.exports.attendance_summary_pdf', compact('employees','monthlySummary','month','search'));
            return $pdf->setPaper('a4','landscape')->download("attendance_summary_{$month}.pdf");
        }
        if ($req->get('export') === 'csv') {
            return $this->exportAttendanceSummaryCsv($employees, $monthlySummary, $month);
        }

        return view('pages.hr.attendance', compact('employees','today','todayRecords','month','monthlySummary','search'));
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

    // ── WORKLOAD ─────────────────────────────────────────────────────────────

    public function workload()
    {
        $teachers = User::where('user_type','teacher')->orderBy('name')->get()->map(function($t) {
            $t->subjects = Subject::where('teacher_id',$t->id)->with('my_class')->get();
            return $t;
        });
        return view('pages.hr.workload', compact('teachers'));
    }
}
