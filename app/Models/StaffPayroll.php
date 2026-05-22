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
            'pending'  => 'warning',
            'approved' => 'info',
            'paid'     => 'success',
            default    => 'secondary',
        };
    }

    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isPending(): bool  { return $this->status === 'pending'; }
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

    // ── Advanced getters ─────────────────────────────────────────────────────

    /**
     * Get gross pay (before deductions)
     */
    public function getGrossPayAttribute(): float
    {
        return round($this->base_salary + $this->allowances, 2);
    }

    /**
     * Get total statutory deductions
     */
    public function getStatutoryDeductionsAttribute(): float
    {
        return round($this->income_tax + $this->employee_pension, 2);
    }

    /**
     * Get breakdown of earnings
     */
    public function getEarningsBreakdown(): array
    {
        return [
            'base_salary' => $this->base_salary,
            'allowances' => $this->allowances,
            'total_earnings' => $this->base_salary + $this->allowances,
        ];
    }

    /**
     * Get breakdown of deductions
     */
    public function getDeductionsBreakdown(): array
    {
        return [
            'income_tax' => $this->income_tax,
            'employee_pension' => $this->employee_pension,
            'other_deductions' => $this->deductions - $this->income_tax - $this->employee_pension,
            'total_deductions' => $this->deductions,
        ];
    }

    /**
     * Calculate effective tax rate
     */
    public function getEffectiveTaxRate(): float
    {
        $gross = $this->base_salary + $this->allowances;
        if ($gross <= 0) return 0;
        return round(($this->income_tax / $gross) * 100, 2);
    }

    /**
     * Get processing time (from creation to approval)
     */
    public function getProcessingTime(): ?string
    {
        if (!$this->approved_at) {
            return null;
        }
        $hours = $this->created_at->diffInHours($this->approved_at);
        $days = floor($hours / 24);
        $remainingHours = $hours % 24;
        
        if ($days > 0) {
            return "{$days}d {$remainingHours}h";
        }
        return "{$remainingHours}h";
    }

    /**
     * Check if payroll is overdue for approval
     */
    public function isOverdueForApproval(): bool
    {
        return $this->isDraft() && $this->created_at->diffInDays(now()) > 7;
    }

    /**
     * Check if payroll is overdue for payment
     */
    public function isOverdueForPayment(): bool
    {
        return $this->isApproved() && $this->approved_at->diffInDays(now()) > 30;
    }

    /**
     * Get status with extended information
     */
    public function getStatusInfo(): array
    {
        $info = [
            'status' => $this->status,
            'badge_class' => $this->statusBadgeClass(),
            'display_name' => ucfirst($this->status),
        ];

        if ($this->isOverdueForApproval()) {
            $info['alert'] = 'Overdue for approval';
            $info['alert_class'] = 'danger';
        } elseif ($this->isOverdueForPayment()) {
            $info['alert'] = 'Overdue for payment';
            $info['alert_class'] = 'warning';
        }

        return $info;
    }
}
