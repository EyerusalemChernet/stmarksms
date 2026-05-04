<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EmploymentDetails — job contract terms for an employee.
 *
 * Separated from Employee so that contract changes (salary, type, department)
 * can be tracked over time without touching the core identity record.
 * Future: this becomes the basis for a contracts/history table.
 */
class EmploymentDetails extends Model
{
    protected $fillable = [
        'employee_id',
        'department_id',
        'position_id',
        'reporting_manager_id',
        'employment_type',
        'hire_date',
        'contract_end_date',
        'currency',
        'salary',
        'is_remote',
        'bank_name',
        'bank_account_no',
    ];

    protected $casts = [
        'hire_date'         => 'date',
        'contract_end_date' => 'date',
        'is_remote'         => 'boolean',
        'salary'            => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function reportingManager()
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function employmentTypeLabel(): string
    {
        return match($this->employment_type) {
            'full_time' => 'Full Time',
            'part_time' => 'Part Time',
            'contract'  => 'Contract',
            'intern'    => 'Intern',
            default     => ucfirst($this->employment_type ?? '—'),
        };
    }

    public function isContractExpired(): bool
    {
        return $this->contract_end_date && $this->contract_end_date->isPast();
    }
}
