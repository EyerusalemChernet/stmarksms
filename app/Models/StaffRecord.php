<?php

namespace App\Models;

use App\User;
use Eloquent;

class StaffRecord extends Eloquent
{
    protected $fillable = [
        // ── Original fields ──────────────────────────────────────────────────
        'user_id',
        'code',
        'emp_date',
        'department_id',
        'bank_acc_no',
        'hired_on',
        'is_remote',

        // ── Step 1: Employment classification ───────────────────────────────
        'employment_type',
        'employment_status',
        'termination_date',
        'termination_reason',

        // ── Step 1: Identity & compliance ───────────────────────────────────
        'national_id',
        'tin_number',
        'pension_number',

        // ── Step 1: Qualification ────────────────────────────────────────────
        'qualification',
        'field_of_study',

        // ── Step 1: Emergency contact ────────────────────────────────────────
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',

        // ── Step 1: HR notes ─────────────────────────────────────────────────
        'hr_notes',
    ];

    protected $casts = [
        'hired_on'         => 'date',
        'termination_date' => 'date',
        'is_remote'        => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Human-readable employment type label */
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

    /** Bootstrap badge colour for employment status */
    public function statusBadgeClass(): string
    {
        return match($this->employment_status) {
            'active'     => 'success',
            'on_leave'   => 'warning',
            'suspended'  => 'danger',
            'terminated' => 'dark',
            default      => 'secondary',
        };
    }

    /** True if this employee is currently active */
    public function isActive(): bool
    {
        return $this->employment_status === 'active';
    }
}
