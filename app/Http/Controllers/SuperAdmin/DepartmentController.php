<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\StaffRecord;
use App\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with([
            'staff' => fn ($q) => $q->whereHas('user', fn ($u) => $u->where('user_type', 'teacher'))->with('user'),
        ])->orderBy('name')->get();

        $unassignedTeachers = $this->unassignedTeachers();

        return view('pages.super_admin.departments.index', compact('departments', 'unassignedTeachers'));
    }

    public function store(Request $req)
    {
        $req->validate([
            'name' => 'required|string|max:100|unique:departments,name',
            'description' => 'nullable|string|max:255',
        ]);

        $dept = Department::create($req->only('name', 'description'));
        AuditLog::log('created', 'departments', "Department '{$dept->name}' created");

        return back()->with('flash_success', 'Department created.');
    }

    public function addTeacher(Request $req, Department $department)
    {
        $req->validate(['user_id' => 'required|integer|exists:users,id']);

        $teacher = User::where('id', $req->user_id)->where('user_type', 'teacher')->firstOrFail();
        $this->assignTeacherToDepartment($teacher, $department);

        AuditLog::log('updated', 'departments', "Teacher '{$teacher->name}' added to '{$department->name}'");

        return back()->with('flash_success', "{$teacher->name} added to {$department->name}.");
    }

    public function removeTeacher(Department $department, User $user)
    {
        if ($user->user_type !== 'teacher') {
            return back()->with('flash_danger', 'Only teachers can be removed from departments.');
        }

        $staff = StaffRecord::where('user_id', $user->id)->first();
        if ($staff && (int) $staff->department_id === (int) $department->id) {
            $staff->department_id = null;
            $staff->save();
            AuditLog::log('updated', 'departments', "Teacher '{$user->name}' removed from '{$department->name}'");
        }

        return back()->with('flash_success', "{$user->name} removed from {$department->name}.");
    }

    protected function assignTeacherToDepartment(User $teacher, Department $department): void
    {
        $staff = StaffRecord::firstOrNew(['user_id' => $teacher->id]);
        if (!$staff->exists) {
            $staff->code = $teacher->code ?: (Qs::getAppCode() . '/STAFF/' . date('Y/m') . '/' . mt_rand(1000, 9999));
            $staff->emp_date = now()->toDateString();
        }
        $staff->department_id = $department->id;
        $staff->save();
    }

    protected function unassignedTeachers()
    {
        return User::where('user_type', 'teacher')
            ->where(function ($q) {
                $q->whereDoesntHave('staff')
                    ->orWhereHas('staff', fn ($s) => $s->whereNull('department_id'));
            })
            ->orderBy('name')
            ->get();
    }
}
