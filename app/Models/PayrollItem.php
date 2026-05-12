<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PayrollItem — a single line in a payroll (earning or deduction).
 *
 * Examples:
 *   earning   | Basic Salary        | 15000.00
 *   earning   | Transport Allowance |  1500.00
 *   earning   | Overtime Pay        |   750.00
 *   deduction | Income Tax          |  1875.00
 *   deduction | Employee Pension    |  1050.00
 *   deduction | Absence Deduction   |   500.00
 */
class PayrollItem extends Model
{
    protected $fillable = ['payroll_id', 'type', 'label', 'amount', 'note'];

    protected $casts = ['amount' => 'decimal:2'];

    public function payroll()
    {
        return $this->belongsTo(StaffPayroll::class, 'payroll_id');
    }

    public function isEarning(): bool
    {
        return $this->type === 'earning';
    }

    public function isDeduction(): bool
    {
        return $this->type === 'deduction';
    }
}
