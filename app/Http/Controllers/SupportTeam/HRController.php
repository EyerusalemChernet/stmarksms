<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\StaffAttendance;
use App\Models\StaffRecord;
use App\Models\Subject;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HRController extends Controller
{
    public function __construct()
    {
        $this->middleware('hr_manager');
    }

    // ── Staff List ───────────────────────────────────────────────────────────────

    public function index()
    {
        $staff = User::whereIn('user_type', ['admin', 'teacher', 'hr_manager', 'super_admin'])
            ->with('staff.department')
            ->orderBy('name')
            ->get();

        $departments = Department::orderBy('name')->get();

        return view('pages.hr.index', compact('staff', 'departments'));
    }

    // ── Staff Profile ────────────────────────────────────────────────────────────

    public function show($id)
    {
        $user = User::with('staff.department')->findOrFail($id);

        // Subjects assigned (if teacher)
        $subjects = Subject::where('teacher_id', $id)->with('my_class')->get();

        // Attendance summary (last 30 days)
        $attendance = StaffAttendance::where('user_id', $id)
            ->orderByDesc('date')->take(30)->get();

        $presentCount = $attendance->whereIn('status', ['present', 'late'])->count();
        $totalCount   = $attendance->count();
        $attPct       = $totalCount > 0 ? round(($presentCount / $totalCount) * 100, 1) : 100;

        return view('pages.hr.show', compact('user', 'subjects', 'attendance', 'attPct', 'presentCount', 'totalCount'));
    }

    // ── Departments ──────────────────────────────────────────────────────────────

    public function departments()
    {
        $departments = Department::withCount('staff')->orderBy('name')->get();
        return view('pages.hr.departments', compact('departments'));
    }

    public function storeDepartment(Request $req)
    {
        $req->validate(['name' => 'required|string|max:100|unique:departments,name']);
        Department::create($req->only('name', 'description'));
        AuditLog::log('created', 'hr', "Department '{$req->name}' created");
        return Qs::jsonStoreOk();
    }

    public function updateDepartment(Request $req, $id)
    {
        $req->validate(['name' => 'required|string|max:100|unique:departments,name,' . $id]);
        $dept = Department::findOrFail($id);
        $dept->update($req->only('name', 'description'));
        AuditLog::log('updated', 'hr', "Department '{$req->name}' updated");
        return Qs::jsonUpdateOk();
    }

    public function destroyDepartment($id)
    {
        Department::findOrFail($id)->delete();
        AuditLog::log('deleted', 'hr', "Department ID {$id} deleted");
        return back()->with('flash_success', 'Department deleted.');
    }

    // ── Assign Department ────────────────────────────────────────────────────────

    public function assignDepartment(Request $req, $user_id)
    {
        $req->validate(['department_id' => 'nullable|exists:departments,id']);
        $sr = StaffRecord::where('user_id', $user_id)->first();
        if ($sr) {
            $sr->update(['department_id' => $req->department_id]);
        } else {
            StaffRecord::create(['user_id' => $user_id, 'department_id' => $req->department_id]);
        }
        AuditLog::log('updated', 'hr', "Department assigned to user ID {$user_id}");
        return back()->with('flash_success', 'Department updated.');
    }

    // ── Staff Attendance ─────────────────────────────────────────────────────────

    public function attendance()
    {
        $staff = User::whereIn('user_type', ['admin', 'teacher', 'hr_manager', 'super_admin'])
            ->orderBy('name')->get();

        $today = now()->toDateString();
        $todayRecords = StaffAttendance::where('date', $today)->pluck('status', 'user_id');

        return view('pages.hr.attendance', compact('staff', 'today', 'todayRecords'));
    }

    public function saveAttendance(Request $req)
    {
        $req->validate(['date' => 'required|date', 'attendance' => 'required|array']);

        foreach ($req->attendance as $userId => $status) {
            StaffAttendance::updateOrCreate(
                ['user_id' => $userId, 'date' => $req->date],
                ['status' => $status]
            );
        }

        AuditLog::log('created', 'hr', "Staff attendance saved for {$req->date}");
        return back()->with('flash_success', 'Staff attendance saved successfully.');
    }

    // ── Workload Overview ────────────────────────────────────────────────────────

    public function workload()
    {
        $teachers = User::where('user_type', 'teacher')->orderBy('name')->get()->map(function ($t) {
            $t->subjects = Subject::where('teacher_id', $t->id)->with('my_class')->get();
            return $t;
        });

        return view('pages.hr.workload', compact('teachers'));
    }
}
