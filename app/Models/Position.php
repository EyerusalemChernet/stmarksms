<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = ['name', 'department_id', 'description'];

    // ── Relationships ────────────────────────────────────────────────────────

    /** Department this position belongs to (nullable = cross-department) */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /** Employees currently in this position (via employment_details) */
    public function employees()
    {
        return $this->hasManyThrough(
            Employee::class,
            EmploymentDetails::class,
            'position_id',
            'id',
            'id',
            'employee_id'
        );
    }

    /** Position history records */
    public function staffPositions()
    {
        return $this->hasMany(StaffPosition::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Count of active employees in this position */
    public function getEmployeeCountAttribute(): int
    {
        return EmploymentDetails::where('position_id', $this->id)
            ->whereHas('employee', fn($q) => $q->where('status', 'active'))
            ->count();
    }
}
