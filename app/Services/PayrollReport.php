<?php

namespace App\Services;

use App\Models\StaffPayroll;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * PayrollReport — Advanced payroll reporting
 *
 * Generates various payroll reports:
 * - Summary reports
 * - Detailed breakdowns
 * - Trend analysis
 * - Compliance reports
 * - Export formats
 */
class PayrollReport
{
    private string $month;
    private Collection $payrolls;

    public function __construct(string $month, Collection $payrolls = null)
    {
        $this->month = $month;
        $this->payrolls = $payrolls ?? StaffPayroll::where('month', $month)->get();
    }

    /**
     * Get summary report
     */
    public function getSummaryReport(): array
    {
        $totalBaseSalary = $this->payrolls->sum('base_salary');
        $totalAllowances = $this->payrolls->sum('allowances');
        $totalDeductions = $this->payrolls->sum('deductions');
        $totalNetPay = $this->payrolls->sum('net_pay');
        $totalIncomeTax = $this->payrolls->sum('income_tax');
        $totalPension = $this->payrolls->sum('employee_pension');
        $totalEmployerPension = $this->payrolls->sum('employer_pension');

        $statusBreakdown = $this->payrolls->groupBy('status')->map->count();

        return [
            'month' => $this->month,
            'total_employees' => $this->payrolls->count(),
            'financial_summary' => [
                'total_base_salary' => round($totalBaseSalary, 2),
                'total_allowances' => round($totalAllowances, 2),
                'total_deductions' => round($totalDeductions, 2),
                'total_net_pay' => round($totalNetPay, 2),
            ],
            'tax_and_deductions' => [
                'total_income_tax' => round($totalIncomeTax, 2),
                'total_employee_pension' => round($totalPension, 2),
                'total_employer_pension' => round($totalEmployerPension, 2),
            ],
            'status_breakdown' => $statusBreakdown->toArray(),
            'statistics' => [
                'average_salary' => $this->payrolls->count() > 0 ? round($totalNetPay / $this->payrolls->count(), 2) : 0,
                'highest_net_pay' => round($this->payrolls->max('net_pay'), 2),
                'lowest_net_pay' => round($this->payrolls->min('net_pay'), 2),
            ],
        ];
    }

    /**
     * Get attendance report
     */
    public function getAttendanceReport(): array
    {
        return [
            'month' => $this->month,
            'total_present_days' => $this->payrolls->sum('present_days'),
            'total_absent_days' => $this->payrolls->sum('absent_days'),
            'total_leave_days' => $this->payrolls->sum('leave_days'),
            'average_present_days' => round($this->payrolls->avg('present_days'), 2),
            'average_absent_days' => round($this->payrolls->avg('absent_days'), 2),
            'employees_with_absence' => $this->payrolls->where('absent_days', '>', 0)->count(),
        ];
    }

    /**
     * Get department-wise breakdown
     */
    public function getDepartmentReport(): array
    {
        return $this->payrolls
            ->load('employee.employmentDetails.department')
            ->groupBy(fn($p) => $p->employee->employmentDetails?->department?->name ?? 'Unassigned')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_base_salary' => round($group->sum('base_salary'), 2),
                    'total_net_pay' => round($group->sum('net_pay'), 2),
                    'total_deductions' => round($group->sum('deductions'), 2),
                    'average_net_pay' => round($group->avg('net_pay'), 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get overtime report
     */
    public function getOvertimeReport(): array
    {
        $overtimePay = $this->payrolls
            ->load('items')
            ->map(function ($payroll) {
                $overtimeItem = $payroll->items()
                    ->where('label', 'like', '%Overtime%')
                    ->first();
                return [
                    'employee' => $payroll->employee->full_name,
                    'overtime_hours' => $payroll->overtime_hours,
                    'overtime_pay' => $overtimeItem?->amount ?? 0,
                ];
            })
            ->filter(fn($item) => $item['overtime_hours'] > 0)
            ->values();

        return [
            'month' => $this->month,
            'total_overtime_hours' => $this->payrolls->sum('overtime_hours'),
            'total_overtime_pay' => round($overtimePay->sum('overtime_pay'), 2),
            'employees_with_overtime' => $overtimePay->count(),
            'details' => $overtimePay->toArray(),
        ];
    }

    /**
     * Get compliance report
     */
    public function getComplianceReport(): array
    {
        $draft = $this->payrolls->where('status', 'draft')->count();
        $approved = $this->payrolls->where('status', 'approved')->count();
        $paid = $this->payrolls->where('status', 'paid')->count();
        $pending = $this->payrolls->where('status', 'pending')->count();

        return [
            'month' => $this->month,
            'processing_status' => [
                'draft' => $draft,
                'approved' => $approved,
                'paid' => $paid,
                'pending' => $pending,
            ],
            'completion_percentage' => $this->payrolls->count() > 0 ? round(($paid / $this->payrolls->count()) * 100, 2) : 0,
            'average_approval_time' => $this->getAverageApprovalTime(),
            'unprocessed_count' => $draft + $pending,
        ];
    }

    /**
     * Get payroll comparison with previous month
     */
    public function getComparisonReport(string $previousMonth): array
    {
        $previousPayrolls = StaffPayroll::where('month', $previousMonth)->get();

        $currentSummary = $this->getSummaryReport();
        $previousSummary = [
            'total_base_salary' => $previousPayrolls->sum('base_salary'),
            'total_net_pay' => $previousPayrolls->sum('net_pay'),
        ];

        return [
            'current_month' => $this->month,
            'previous_month' => $previousMonth,
            'salary_change' => [
                'percentage' => round(
                    (($currentSummary['financial_summary']['total_base_salary'] - $previousSummary['total_base_salary']) /
                    $previousSummary['total_base_salary']) * 100,
                    2
                ),
                'absolute' => round(
                    $currentSummary['financial_summary']['total_base_salary'] - $previousSummary['total_base_salary'],
                    2
                ),
            ],
            'net_pay_change' => [
                'percentage' => round(
                    (($currentSummary['financial_summary']['total_net_pay'] - $previousSummary['total_net_pay']) /
                    $previousSummary['total_net_pay']) * 100,
                    2
                ),
                'absolute' => round(
                    $currentSummary['financial_summary']['total_net_pay'] - $previousSummary['total_net_pay'],
                    2
                ),
            ],
        ];
    }

    /**
     * Get average approval time
     */
    private function getAverageApprovalTime(): ?string
    {
        $approvedPayrolls = $this->payrolls->filter(fn($p) => $p->approved_at);

        if ($approvedPayrolls->isEmpty()) {
            return null;
        }

        $totalHours = $approvedPayrolls->reduce(function ($carry, $payroll) {
            if ($payroll->created_at && $payroll->approved_at) {
                $hours = $payroll->created_at->diffInHours($payroll->approved_at);
                return $carry + $hours;
            }
            return $carry;
        }, 0);

        $avgHours = round($totalHours / $approvedPayrolls->count());
        return "{$avgHours} hours";
    }

    /**
     * Export data to array format
     */
    public function exportArray(): array
    {
        return $this->payrolls
            ->map(function ($payroll) {
                return [
                    'employee_id' => $payroll->employee_id,
                    'employee_code' => $payroll->employee->employee_code,
                    'employee_name' => $payroll->employee->full_name,
                    'month' => $payroll->month,
                    'base_salary' => $payroll->base_salary,
                    'allowances' => $payroll->allowances,
                    'deductions' => $payroll->deductions,
                    'income_tax' => $payroll->income_tax,
                    'employee_pension' => $payroll->employee_pension,
                    'net_pay' => $payroll->net_pay,
                    'status' => $payroll->status,
                    'approved_by' => $payroll->approvedBy?->name,
                    'approved_at' => $payroll->approved_at?->toDateString(),
                    'paid_at' => $payroll->paid_at?->toDateString(),
                ];
            })
            ->toArray();
    }
}
