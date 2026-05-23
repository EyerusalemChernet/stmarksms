<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\StaffRecord;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['teachers'])->orderBy('name')->get();
        $allTeachers = User::where('user_type', 'teacher')->orderBy('name')->get();

        return view('pages.super_admin.departments.index', compact('departments', 'allTeachers'));
    }

    public function store(Request $req)
    {
        $req->validate([
            'name'        => 'required|string|max:100|unique:departments,name',
            'description' => 'nullable|string|max:255',
        ]);

        $dept = Department::create($req->only('name', 'description'));
        AuditLog::log('created', 'departments', "Department '{$dept->name}' created");

        return back()->with('flash_success', 'Department created.');
    }

    public function addTeacher(Request $req, Department $department)
    {
        $req->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $added = 0;
        foreach ($req->user_ids as $userId) {
            $teacher = User::where('id', $userId)->where('user_type', 'teacher')->first();
            if (!$teacher) continue;

            // Add to pivot (ignore if already exists)
            DB::table('department_teacher')->insertOrIgnore([
                'department_id' => $department->id,
                'user_id'       => $userId,
            ]);

            // Also update staff_records.department_id for backward compat (last assigned wins)
            $staff = StaffRecord::firstOrNew(['user_id' => $userId]);
            if (!$staff->exists) {
                $staff->code     = Qs::getAppCode() . '/STAFF/' . date('Y/m') . '/' . mt_rand(1000, 9999);
                $staff->emp_date = now()->toDateString();
            }
            $staff->department_id = $department->id;
            $staff->save();

            $added++;
        }

        AuditLog::log('updated', 'departments', "{$added} teacher(s) added to '{$department->name}'");
        return back()->with('flash_success', "{$added} teacher(s) added to {$department->name}.");
    }

    public function removeTeacher(Department $department, User $user)
    {
        if ($user->user_type !== 'teacher') {
            return back()->with('flash_danger', 'Only teachers can be removed from departments.');
        }

        DB::table('department_teacher')
            ->where('department_id', $department->id)
            ->where('user_id', $user->id)
            ->delete();

        AuditLog::log('updated', 'departments', "Teacher '{$user->name}' removed from '{$department->name}'");
        return back()->with('flash_success', "{$user->name} removed from {$department->name}.");
    }
}
