<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class StaffPayroll extends Model
{
    protected $fillable = [
        'employee_id',
        'user_id',
        'currency',
        'month',
        'period_start',
        'period_end',
        // Attendance snapshot
        'working_days',
        'present_days',
        'absent_days',
        'leave_days',
        'overtime_hours',
        // Pay components
        'base_salary',
        'allowances',
        'deductions',
        'income_tax',
        'employee_pension',
        'employer_pension',
        'net_pay',
        // Workflow
        'status',
        'approved_by',
        'approved_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'approved_at'  => 'datetime',
        'paid_at'      => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class, 'payroll_id');
    }

    public function earnings()
    {
        return $this->hasMany(PayrollItem::class, 'payroll_id')->where('type', 'earning');
    }

    public function deductionItems()
    {
        return $this->hasMany(PayrollItem::class, 'payroll_id')->where('type', 'deduction');
    }

    // ── Status helpers ───────────────────────────────────────────────────────

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'draft'    => 'secondary',
            'approved' => 'info',
            'paid'     => 'success',
            default    => 'secondary',
        };
    }

    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isPaid(): bool     { return $this->status === 'paid'; }

    // ── Recalculate net pay from stored components ───────────────────────────

    public function recalculate(): void
    {
        $this->net_pay = $this->base_salary
            + $this->allowances
            - $this->deductions
            - $this->income_tax
            - $this->employee_pension;
        $this->save();
    }
}
