<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'description'];

    // ── Relationships ────────────────────────────────────────────────────────

    /** Employees in this department (via employment_details) */
    public function employees()
    {
        return $this->hasManyThrough(
            Employee::class,
            EmploymentDetails::class,
            'department_id',  // FK on employment_details
            'id',             // FK on employees
            'id',             // local key on departments
            'employee_id'     // local key on employment_details
        );
    }

    /** Positions belonging to this department */
    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    /** Legacy: staff_records (school system — kept for backward compat) */
    public function staff()
    {
        return $this->hasMany(StaffRecord::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Count of active employees in this department */
    public function getEmployeeCountAttribute(): int
    {
        return EmploymentDetails::where('department_id', $this->id)
            ->whereHas('employee', fn($q) => $q->where('status', 'active'))
            ->count();
    }
}
