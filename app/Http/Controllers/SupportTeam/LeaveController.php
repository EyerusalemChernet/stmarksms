<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use PDF;

class LeaveController extends Controller
{
    protected LeaveService $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        // HR admin routes require hr_manager middleware
        $this->middleware('hr_manager')->except([
            'myLeaveIndex','myLeaveCreate','myLeaveStore','myLeaveShow','myLeaveCancel',
        ]);
        // Self-service routes only need auth (handled by route group)
        $this->leaveService = $leaveService;
    }

    // ── LEAVE POLICIES ───────────────────────────────────────────────────────

    public function policies()
    {
        $year     = request('year', now()->year);
        $policies = LeavePolicy::where('year',$year)->orderBy('leave_type')->get();
        return view('pages.hr.leave.policies', compact('policies','year'));
    }

    public function storePolicy(Request $req)
    {
        $req->validate(['leave_type'=>'required|in:annual,sick,maternity,paternity,unpaid,other','year'=>'required|integer|min:2020|max:2099','days_entitled'=>'required|integer|min:0|max:365','is_paid'=>'nullable|boolean','carry_forward'=>'nullable|boolean','description'=>'nullable|string|max:255']);
        LeavePolicy::updateOrCreate(
            ['leave_type'=>$req->leave_type,'year'=>$req->year],
            ['days_entitled'=>$req->days_entitled,'is_paid'=>$req->boolean('is_paid',true),'carry_forward'=>$req->boolean('carry_forward',false),'description'=>$req->description]
        );
        AuditLog::log('created','hr',"Leave policy updated: {$req->leave_type} {$req->year}");
        return back()->with('flash_success','Leave policy saved.');
    }

    public function destroyPolicy($hrId)
    {
        LeavePolicy::findOrFail($hrId)->delete();
        AuditLog::log('deleted','hr',"Leave policy ID {$hrId} deleted");
        return back()->with('flash_success','Policy deleted.');
    }

    public function initBalances(Request $req)
    {
        $req->validate(['year'=>'required|integer|min:2020|max:2099']);
        $count = $this->leaveService->initialiseAllBalances((int)$req->year);
        AuditLog::log('created','hr',"Leave balances initialised for {$req->year} — {$count} employees");
        return back()->with('flash_success',"Leave balances initialised for {$req->year}. {$count} employees updated.");
    }

    // ── LEAVE REQUESTS ───────────────────────────────────────────────────────

    public function requests(Request $req)
    {
        $status   = $req->get('status','pending');
        $month    = $req->get('month');
        $search   = trim($req->get('search',''));

        $query = LeaveRequest::with('employee')
            ->when($status !== 'all', fn($q) => $q->where('status',$status))
            ->when($month, fn($q) => $q->where('start_date','like',$month.'%'))
            ->when($search, fn($q) => $q->whereHas('employee', fn($i) =>
                $i->where('first_name','like',"%{$search}%")
                  ->orWhere('last_name','like',"%{$search}%")
                  ->orWhere('employee_code','like',"%{$search}%")
            ))
            ->orderByDesc('created_at');

        $statusCounts = array_merge(
            ['pending'=>0,'approved'=>0,'rejected'=>0,'cancelled'=>0],
            LeaveRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status')->toArray()
        );

        if ($req->get('export') === 'pdf') {
            $requests = $query->get();
            $pdf = PDF::loadView('pages.hr.exports.leave_requests_pdf', compact('requests','status','search','month'));
            return $pdf->setPaper('a4','landscape')->download("leave_requests_{$status}.pdf");
        }
        if ($req->get('export') === 'csv') {
            $requests = $query->get();
            return $this->exportLeaveRequestsCsv($requests, $status);
        }

        $requests = $query->paginate(20);
        return view('pages.hr.leave.requests', compact('requests','status','statusCounts','month','search'));
    }

    protected function exportLeaveRequestsCsv($requests, $status)
    {
        $filename = "leave_requests_{$status}_".now()->format('Y-m-d').".csv";
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>"attachment; filename={$filename}"];
        $callback = function () use ($requests) {
            $h = fopen('php://output','w');
            fputcsv($h, ['ID','Employee','Code','Leave Type','Start Date','End Date','Days','Status','Reason','Submitted']);
            foreach ($requests as $r) {
                fputcsv($h, [
                    $r->id,
                    $r->employee->full_name,
                    $r->employee->employee_code,
                    $r->leaveTypeLabel(),
                    $r->start_date->format('Y-m-d'),
                    $r->end_date->format('Y-m-d'),
                    $r->days_requested,
                    ucfirst($r->status),
                    $r->reason ?? '',
                    $r->created_at->format('Y-m-d'),
                ]);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function createRequest()
    {
        $employees = Employee::where('status','active')->orderBy('first_name')->get();
        return view('pages.hr.leave.request_create', compact('employees'));
    }

    public function storeRequest(Request $req)
    {
        $req->validate(['employee_id'=>'required|exists:employees,id','leave_type'=>'required|in:annual,sick,maternity,paternity,unpaid,other','start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','reason'=>'nullable|string|max:500']);
        $employee = Employee::findOrFail($req->employee_id);
        try {
            $this->leaveService->submit($employee, $req->all());
            return redirect()->route('hr.leave.requests')->with('flash_success','Leave request submitted.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('flash_danger',$e->getMessage());
        }
    }

    public function showRequest($hrId)
    {
        $request = LeaveRequest::with('employee','reviewedBy')->findOrFail($hrId);
        $year    = $request->start_date->year;
        $balance = $this->leaveService->getBalance($request->employee, $request->leave_type, $year);
        return view('pages.hr.leave.request_show', compact('request','balance'));
    }

    public function approveRequest(Request $req, $hrId)
    {
        $req->validate(['comment'=>'nullable|string|max:500']);
        $leaveRequest = LeaveRequest::findOrFail($hrId);
        try {
            $this->leaveService->approve($leaveRequest, auth()->id(), $req->comment);
            return back()->with('flash_success','Leave request approved. Attendance records created.');
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger',$e->getMessage());
        }
    }

    public function rejectRequest(Request $req, $hrId)
    {
        $req->validate(['comment'=>'nullable|string|max:500']);
        $leaveRequest = LeaveRequest::findOrFail($hrId);
        try {
            $this->leaveService->reject($leaveRequest, auth()->id(), $req->comment);
            return back()->with('flash_success','Leave request rejected.');
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger',$e->getMessage());
        }
    }

    public function cancelRequest($hrId)
    {
        $leaveRequest = LeaveRequest::findOrFail($hrId);
        try {
            $this->leaveService->cancel($leaveRequest, auth()->id());
            return back()->with('flash_success','Leave request cancelled.');
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger',$e->getMessage());
        }
    }

    // ── LEAVE BALANCES ───────────────────────────────────────────────────────

    public function balances(Request $req)
    {
        $year        = $req->get('year', now()->year);
        $search      = trim($req->get('search',''));
        $employees   = Employee::where('status','active')
            ->with(['employmentDetails.department'])
            ->when($search, fn($q) => $q->where(fn($i) =>
                $i->where('first_name','like',"%{$search}%")
                  ->orWhere('last_name','like',"%{$search}%")
                  ->orWhere('employee_code','like',"%{$search}%")
            ))
            ->orderBy('first_name')->get();
        $allBalances = LeaveBalance::where('year',$year)->get()->groupBy('employee_id')->map(fn($g)=>$g->keyBy('leave_type'));

        if ($req->get('export') === 'pdf') {
            $pdf = PDF::loadView('pages.hr.exports.leave_balances_pdf', compact('employees','allBalances','year','search'));
            return $pdf->setPaper('a4','landscape')->download("leave_balances_{$year}.pdf");
        }
        if ($req->get('export') === 'csv') {
            return $this->exportLeaveBalancesCsv($employees, $allBalances, $year);
        }

        return view('pages.hr.leave.balances', compact('employees','year','allBalances','search'));
    }

    protected function exportLeaveBalancesCsv($employees, $allBalances, $year)
    {
        $filename = "leave_balances_{$year}.csv";
        $headers  = ['Content-Type'=>'text/csv','Content-Disposition'=>"attachment; filename={$filename}"];
        $callback = function () use ($employees, $allBalances, $year) {
            $h = fopen('php://output','w');
            fputcsv($h, ["Leave Balances — {$year}"]);
            fputcsv($h, []);
            fputcsv($h, ['Employee','Code','Department','Annual (avail/entitled)','Sick','Maternity','Paternity','Unpaid']);
            foreach ($employees as $emp) {
                $b = $allBalances->get($emp->id, collect());
                $row = [$emp->full_name, $emp->employee_code, $emp->employmentDetails?->department?->name ?? ''];
                foreach (['annual','sick','maternity','paternity','unpaid'] as $type) {
                    $bal = $b->get($type);
                    $row[] = $bal ? "{$bal->available}/{$bal->entitled}" : '—';
                }
                fputcsv($h, $row);
            }
            fclose($h);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function employeeBalance($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $year     = request('year', now()->year);
        $summary  = $this->leaveService->employeeBalanceSummary($employee, (int)$year);
        $requests = LeaveRequest::where('employee_id',$employeeId)->whereYear('start_date',$year)->orderByDesc('start_date')->get();
        return view('pages.hr.leave.employee_balance', compact('employee','year','summary','requests'));
    }

    // ── SELF-SERVICE (teachers / all staff) ──────────────────────────────────

    /**
     * Resolve the employee record for the currently logged-in user.
     * Returns null if the user has no linked employee record.
     */
    protected function resolveMyEmployee(): ?Employee
    {
        return Employee::where('user_id', auth()->id())->first();
    }

    /** My leave requests list */
    public function myLeaveIndex()
    {
        $employee = $this->resolveMyEmployee();
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('flash_danger', 'No employee record is linked to your account. Contact HR.');
        }

        $year     = request('year', now()->year);
        $requests = LeaveRequest::where('employee_id', $employee->id)
            ->whereYear('start_date', $year)
            ->orderByDesc('start_date')
            ->get();

        $summary  = $this->leaveService->employeeBalanceSummary($employee, (int)$year);

        return view('pages.hr.leave.my_leave', compact('employee','requests','summary','year'));
    }

    /** Show the apply-for-leave form */
    public function myLeaveCreate()
    {
        $employee = $this->resolveMyEmployee();
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('flash_danger', 'No employee record is linked to your account. Contact HR.');
        }

        $year    = now()->year;
        $summary = $this->leaveService->employeeBalanceSummary($employee, $year);

        return view('pages.hr.leave.my_leave_create', compact('employee','summary'));
    }

    /** Store a self-submitted leave request */
    public function myLeaveStore(Request $req)
    {
        $employee = $this->resolveMyEmployee();
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('flash_danger', 'No employee record is linked to your account. Contact HR.');
        }

        $req->validate([
            'leave_type' => 'required|in:annual,sick,maternity,paternity,unpaid,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string|max:500',
        ]);

        try {
            $leaveRequest = $this->leaveService->submit($employee, $req->all());
            AuditLog::log('created', 'hr', "Self-service leave request #{$leaveRequest->id} by {$employee->full_name}");
            return redirect()->route('my.leave.show', $leaveRequest->id)
                ->with('flash_success', 'Leave request submitted. Awaiting HR approval.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('flash_danger', $e->getMessage());
        }
    }

    /** View a single self-service leave request */
    public function myLeaveShow($leaveId)
    {
        $employee = $this->resolveMyEmployee();
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('flash_danger', 'No employee record is linked to your account.');
        }

        // Employees can only see their own requests
        $leaveRequest = LeaveRequest::where('employee_id', $employee->id)
            ->findOrFail($leaveId);

        $year    = $leaveRequest->start_date->year;
        $balance = $this->leaveService->getBalance($employee, $leaveRequest->leave_type, $year);

        return view('pages.hr.leave.my_leave_show', compact('leaveRequest','employee','balance'));
    }

    /** Cancel own pending leave request */
    public function myLeaveCancel($leaveId)
    {
        $employee = $this->resolveMyEmployee();
        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('flash_danger', 'No employee record is linked to your account.');
        }

        $leaveRequest = LeaveRequest::where('employee_id', $employee->id)
            ->findOrFail($leaveId);

        try {
            $this->leaveService->cancel($leaveRequest, auth()->id());
            return back()->with('flash_success', 'Leave request cancelled.');
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        }
    }
}
