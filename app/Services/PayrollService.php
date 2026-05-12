<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\StaffPayroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PayrollService
 *
 * All payroll calculation and workflow logic lives here.
 *
 * Ethiopian tax rules applied:
 *   - Income tax: progressive brackets (0–600 ETB = 0%, up to >10,000 = 35%)
 *   - Employee pension: 7% of gross
 *   - Employer pension: 11% of gross
 *
 * Overtime rate: 1.25× hourly rate (standard Ethiopian Labour Law)
 * Absence deduction: daily rate × absent days (holidays excluded)
 */
class PayrollService
{
    protected EthiopianHolidayService $holidays;

    public function __construct(EthiopianHolidayService $holidays)
    {
        $this->holidays = $holidays;
    }
    // ── Ethiopian income tax brackets (monthly gross in ETB) ────────────────
    // Source: Ethiopian Revenue and Customs Authority
    private const TAX_BRACKETS = [
        [0,      600,   0,    0     ],
        [601,    1650,  10,   60    ],
        [1651,   3200,  15,   142.5 ],
        [3201,   5250,  20,   302.5 ],
        [5251,   7800,  25,   565   ],
        [7801,   10900, 30,   955   ],
        [10901,  PHP_INT_MAX, 35, 1500],
    ];

    private const EMPLOYEE_PENSION_RATE = 0.07;  // 7%
    private const EMPLOYER_PENSION_RATE = 0.11;  // 11%
    private const OVERTIME_MULTIPLIER   = 1.25;  // 125% of hourly rate

    // ── Tax calculation ──────────────────────────────────────────────────────

    /**
     * Calculate Ethiopian income tax for a given monthly gross salary.
     *
     * @param  float $gross  Monthly gross in ETB
     * @return float
     */
    public function calculateIncomeTax(float $gross): float
    {
        foreach (self::TAX_BRACKETS as [$min, $max, $rate, $deductible]) {
            if ($gross >= $min && $gross <= $max) {
                return round(($gross * $rate / 100) - $deductible, 2);
            }
        }
        return 0;
    }

    /**
     * Calculate employee pension contribution (7% of gross).
     */
    public function calculateEmployeePension(float $gross): float
    {
        return round($gross * self::EMPLOYEE_PENSION_RATE, 2);
    }

    /**
     * Calculate employer pension contribution (11% of gross).
     */
    public function calculateEmployerPension(float $gross): float
    {
        return round($gross * self::EMPLOYER_PENSION_RATE, 2);
    }

    // ── Payroll generation ───────────────────────────────────────────────────

    /**
     * Generate payroll for a single employee for a given month.
     * Uses attendance data from AttendanceService.
     * Skips if a payroll already exists for this employee/month.
     *
     * @param  Employee          $employee
     * @param  string            $month       Y-m
     * @param  AttendanceService $attendance
     * @return StaffPayroll|null  null if skipped (already exists)
     */
    public function generateForEmployee(
        Employee $employee,
        string $month,
        AttendanceService $attendance
    ): ?StaffPayroll {

        // Skip if already generated
        if (StaffPayroll::where('employee_id', $employee->id)->where('month', $month)->exists()) {
            return null;
        }

        $ed = $employee->employmentDetails;
        if (!$ed) return null;

        $baseSalary = (float) ($ed->salary ?? 0);
        $currency   = $ed->currency ?? 'ETB';

        // ── Attendance snapshot ──────────────────────────────────────────────
        $attSummary  = $attendance->monthlySummary($employee->id, $month);
        $workingDays = $attSummary['present'] + $attSummary['late'] + $attSummary['absent'];
        $presentDays = $attSummary['present'] + $attSummary['late'];
        $absentDays  = $attSummary['absent'];
        $leaveDays   = $attSummary['leave'];
        $overtimeHrs = $attSummary['total_overtime_hours'];

        // ── Daily and hourly rates ───────────────────────────────────────────
        // Use actual working days in the month (excluding weekends AND holidays)
        $periodStart    = Carbon::parse($month . '-01')->toDateString();
        $periodEnd      = Carbon::parse($month . '-01')->endOfMonth()->toDateString();
        $workingDaysInMonth = $this->holidays->workingDaysBetween($periodStart, $periodEnd);
        $workingDaysInMonth = max(1, $workingDaysInMonth); // safety guard

        $dailyRate   = $baseSalary / $workingDaysInMonth;
        $shift       = $employee->currentShift?->shift;
        $shiftHours  = $shift ? $shift->durationHours() : 8;
        $hourlyRate  = $shiftHours > 0 ? $dailyRate / $shiftHours : 0;

        // ── Earnings ─────────────────────────────────────────────────────────
        $overtimePay = round($overtimeHrs * $hourlyRate * self::OVERTIME_MULTIPLIER, 2);

        // ── Deductions ───────────────────────────────────────────────────────
        $absenceDeduction = round($absentDays * $dailyRate, 2);

        // Gross = base + overtime - absence deduction
        $gross = $baseSalary + $overtimePay - $absenceDeduction;
        $gross = max(0, $gross);

        // Ethiopian statutory deductions
        $incomeTax       = $currency === 'ETB' ? $this->calculateIncomeTax($gross) : 0;
        $employeePension = $currency === 'ETB' ? $this->calculateEmployeePension($gross) : 0;
        $employerPension = $currency === 'ETB' ? $this->calculateEmployerPension($gross) : 0;

        $totalDeductions = $absenceDeduction + $incomeTax + $employeePension;
        $totalAllowances = $overtimePay;
        $netPay          = $baseSalary + $totalAllowances - $totalDeductions;
        $netPay          = max(0, round($netPay, 2));

        // ── Period dates (already calculated above) ─────────────────────────

        return DB::transaction(function () use (
            $employee, $month, $currency, $baseSalary,
            $periodStart, $periodEnd,
            $workingDays, $presentDays, $absentDays, $leaveDays, $overtimeHrs,
            $totalAllowances, $totalDeductions, $incomeTax, $employeePension, $employerPension,
            $netPay, $overtimePay, $absenceDeduction
        ) {
            $payroll = StaffPayroll::create([
                'employee_id'      => $employee->id,
                'user_id'          => $employee->user_id,
                'currency'         => $currency,
                'month'            => $month,
                'period_start'     => $periodStart,
                'period_end'       => $periodEnd,
                'working_days'     => $workingDays,
                'present_days'     => $presentDays,
                'absent_days'      => $absentDays,
                'leave_days'       => $leaveDays,
                'overtime_hours'   => $overtimeHrs,
                'base_salary'      => $baseSalary,
                'allowances'       => $totalAllowances,
                'deductions'       => $totalDeductions,
                'income_tax'       => $incomeTax,
                'employee_pension' => $employeePension,
                'employer_pension' => $employerPension,
                'net_pay'          => $netPay,
                'status'           => 'draft',
            ]);

            // ── Create manual line items ONLY ────────────────────────────────
            // Base salary, income tax, and pension are stored in dedicated
            // columns on staff_payrolls — NOT as PayrollItems.
            // PayrollItems are reserved for manual entries only:
            //   - Overtime pay (auto-calculated but manual in nature)
            //   - Absence deduction (auto-calculated but manual in nature)
            //   - Bonuses, allowances, penalties added by HR later

            if ($overtimePay > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type'       => 'earning',
                    'label'      => 'Overtime Pay',
                    'amount'     => $overtimePay,
                    'note'       => "{$overtimeHrs}h × 1.25×",
                ]);
            }

            if ($absenceDeduction > 0) {
                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'type'       => 'deduction',
                    'label'      => 'Absence Deduction',
                    'amount'     => $absenceDeduction,
                    'note'       => "{$absentDays} day(s)",
                ]);
            }

            AuditLog::log(
                'created', 'hr',
                "Payroll generated for {$employee->employee_code} — {$month} | Net: {$netPay} {$currency}"
            );

            return $payroll;
        });
    }

    /**
     * Generate payroll for ALL active employees for a given month.
     *
     * @param  string            $month
     * @param  AttendanceService $attendance
     * @return array  ['generated'=>int, 'skipped'=>int]
     */
    public function generateBulk(string $month, AttendanceService $attendance): array
    {
        $employees = Employee::where('status', 'active')
            ->with(['employmentDetails', 'currentShift.shift'])
            ->get();

        $generated = 0;
        $skipped   = 0;

        foreach ($employees as $emp) {
            $result = $this->generateForEmployee($emp, $month, $attendance);
            $result ? $generated++ : $skipped++;
        }

        return compact('generated', 'skipped');
    }

    /**
     * Add a manual line item to an existing draft payroll and recalculate.
     *
     * @param  StaffPayroll $payroll
     * @param  string       $type    'earning' | 'deduction'
     * @param  string       $label
     * @param  float        $amount
     * @param  string|null  $note
     */
    public function addItem(StaffPayroll $payroll, string $type, string $label, float $amount, ?string $note = null): void
    {
        if (!$payroll->isDraft()) {
            throw new \RuntimeException('Cannot modify a payroll that is not in draft status.');
        }

        PayrollItem::create([
            'payroll_id' => $payroll->id,
            'type'       => $type,
            'label'      => $label,
            'amount'     => $amount,
            'note'       => $note,
        ]);

        $this->recalculateFromItems($payroll);
    }

    /**
     * Remove a line item and recalculate.
     */
    public function removeItem(StaffPayroll $payroll, int $itemId): void
    {
        if (!$payroll->isDraft()) {
            throw new \RuntimeException('Cannot modify a payroll that is not in draft status.');
        }

        PayrollItem::where('id', $itemId)->where('payroll_id', $payroll->id)->delete();
        $this->recalculateFromItems($payroll);
    }

    /**
     * Recalculate payroll totals from its line items.
     *
     * Formula:
     *   Earnings   = base_salary + sum(items where type=earning)
     *   Deductions = income_tax + employee_pension + sum(items where type=deduction)
     *   Net Pay    = Earnings - Deductions
     *
     * Base salary, income tax, and pension are stored in dedicated columns
     * and are NOT in payroll_items — they must not be double-counted.
     * When base_salary changes, tax and pension are recalculated automatically.
     */
    public function recalculateFromItems(StaffPayroll $payroll): void
    {
        $payroll->refresh(); // ensure we have latest base_salary
        $items = $payroll->items()->get();

        // Manual items only (no statutory items in this table)
        $manualEarnings   = $items->where('type', 'earning')->sum('amount');
        $manualDeductions = $items->where('type', 'deduction')->sum('amount');

        // Recalculate statutory deductions from current base_salary
        $gross           = $payroll->base_salary + $manualEarnings;
        $incomeTax       = $payroll->currency === 'ETB' ? $this->calculateIncomeTax($gross) : 0;
        $employeePension = $payroll->currency === 'ETB' ? $this->calculateEmployeePension($gross) : 0;
        $employerPension = $payroll->currency === 'ETB' ? $this->calculateEmployerPension($gross) : 0;

        $totalDeductions = $incomeTax + $employeePension + $manualDeductions;
        $netPay          = max(0, round($gross - $totalDeductions, 2));

        $payroll->update([
            'allowances'       => $manualEarnings,
            'deductions'       => $totalDeductions,
            'income_tax'       => $incomeTax,
            'employee_pension' => $employeePension,
            'employer_pension' => $employerPension,
            'net_pay'          => $netPay,
        ]);
    }

    // ── Workflow ─────────────────────────────────────────────────────────────

    /**
     * Approve a payroll (draft → approved).
     */
    public function approve(StaffPayroll $payroll, int $approvedByUserId): void
    {
        if (!$payroll->isDraft()) {
            throw new \RuntimeException('Only draft payrolls can be approved.');
        }

        $payroll->update([
            'status'      => 'approved',
            'approved_by' => $approvedByUserId,
            'approved_at' => now(),
        ]);

        AuditLog::log('updated', 'hr', "Payroll #{$payroll->id} approved by user {$approvedByUserId}");
    }

    /**
     * Mark a payroll as paid (approved → paid).
     */
    public function markPaid(StaffPayroll $payroll, int $approvedByUserId): void
    {
        if (!$payroll->isApproved()) {
            throw new \RuntimeException('Only approved payrolls can be marked as paid.');
        }

        $payroll->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        AuditLog::log('updated', 'hr', "Payroll #{$payroll->id} marked as paid");
    }

    /**
     * Revert an approved payroll back to draft for editing.
     */
    public function revertToDraft(StaffPayroll $payroll): void
    {
        if ($payroll->isPaid()) {
            throw new \RuntimeException('Paid payrolls cannot be reverted.');
        }

        $payroll->update([
            'status'      => 'draft',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        AuditLog::log('updated', 'hr', "Payroll #{$payroll->id} reverted to draft");
    }
}
