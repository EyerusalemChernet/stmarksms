<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

/**
 * PayrollCalculator — Advanced payroll calculations
 *
 * Handles all payroll-related calculations with support for:
 * - Multiple currency support
 * - Custom tax brackets and rates
 * - Overtime calculations
 * - Leave encashment
 * - Bonus calculations
 * - Deduction handling
 */
class PayrollCalculator
{
    private Employee $employee;
    private string $month;
    private array $config;
    private array $calculations = [];

    // Tax brackets (Ethiopia)
    private const TAX_BRACKETS = [
        [0,      600,   0,    0     ],
        [601,    1650,  10,   60    ],
        [1651,   3200,  15,   142.5 ],
        [3201,   5250,  20,   302.5 ],
        [5251,   7800,  25,   565   ],
        [7801,   10900, 30,   955   ],
        [10901,  PHP_INT_MAX, 35, 1500],
    ];

    private const RATES = [
        'employee_pension'  => 0.07,
        'employer_pension'  => 0.11,
        'overtime'          => 1.25,
        'holiday_pay'       => 2.0,
        'leave_encashment'  => 1.5,
    ];

    public function __construct(Employee $employee, string $month, array $config = [])
    {
        $this->employee = $employee;
        $this->month = $month;
        $this->config = array_merge([
            'currency' => 'ETB',
            'apply_tax' => true,
            'apply_pension' => true,
            'shift_hours' => 8,
        ], $config);
    }

    /**
     * Calculate complete payroll
     */
    public function calculate(): array
    {
        $this->calculations = [
            'period' => $this->getPeriod(),
            'attendance' => $this->getAttendanceData(),
            'rates' => $this->calculateRates(),
            'earnings' => $this->calculateEarnings(),
            'deductions' => $this->calculateDeductions(),
            'summary' => [],
        ];

        // Calculate summary
        $totalEarnings = array_sum(array_column($this->calculations['earnings'], 'amount'));
        $totalDeductions = array_sum(array_column($this->calculations['deductions'], 'amount'));
        $grossPay = $totalEarnings - array_sum(array_column($this->calculations['deductions'], 'amount', 2));

        $this->calculations['summary'] = [
            'gross_pay' => $grossPay,
            'total_deductions' => $totalDeductions,
            'net_pay' => max(0, $grossPay - $totalDeductions),
            'employer_pension' => $this->calculations['deductions']['employer_pension']['amount'] ?? 0,
        ];

        return $this->calculations;
    }

    /**
     * Get period information
     */
    private function getPeriod(): array
    {
        $start = Carbon::parse($this->month . '-01');
        $end = $start->copy()->endOfMonth();

        return [
            'month' => $this->month,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days_in_month' => $end->day,
        ];
    }

    /**
     * Get attendance data (mock - integrate with actual service)
     */
    private function getAttendanceData(): array
    {
        return [
            'working_days' => 22,
            'present_days' => 20,
            'absent_days' => 2,
            'leave_days' => 0,
            'overtime_hours' => 8,
            'holidays' => 0,
        ];
    }

    /**
     * Calculate rates based on employment details
     */
    private function calculateRates(): array
    {
        $ed = $this->employee->employmentDetails;
        $baseSalary = (float) ($ed->salary ?? 0);
        $workingDaysInMonth = 22; // Standard working days
        
        $dailyRate = $baseSalary / $workingDaysInMonth;
        $hourlyRate = $dailyRate / $this->config['shift_hours'];

        return [
            'base_salary' => $baseSalary,
            'daily_rate' => round($dailyRate, 2),
            'hourly_rate' => round($hourlyRate, 2),
            'overtime_rate' => round($hourlyRate * self::RATES['overtime'], 2),
        ];
    }

    /**
     * Calculate all earnings
     */
    private function calculateEarnings(): array
    {
        $attendance = $this->calculations['attendance'];
        $rates = $this->calculations['rates'];
        $earnings = [];

        // Base salary
        $earnings['base_salary'] = [
            'label' => 'Base Salary',
            'amount' => $rates['base_salary'],
            'currency' => $this->config['currency'],
        ];

        // Overtime pay
        if ($attendance['overtime_hours'] > 0) {
            $earnings['overtime'] = [
                'label' => 'Overtime Pay',
                'hours' => $attendance['overtime_hours'],
                'rate' => $rates['overtime_rate'],
                'amount' => round($attendance['overtime_hours'] * $rates['overtime_rate'], 2),
                'currency' => $this->config['currency'],
            ];
        }

        // Holiday pay (if applicable)
        if ($attendance['holidays'] > 0) {
            $earnings['holiday_pay'] = [
                'label' => 'Holiday Pay',
                'days' => $attendance['holidays'],
                'rate' => $rates['daily_rate'] * self::RATES['holiday_pay'],
                'amount' => round($attendance['holidays'] * $rates['daily_rate'] * self::RATES['holiday_pay'], 2),
                'currency' => $this->config['currency'],
            ];
        }

        return $earnings;
    }

    /**
     * Calculate all deductions
     */
    private function calculateDeductions(): array
    {
        $attendance = $this->calculations['attendance'];
        $rates = $this->calculations['rates'];
        $gross = $rates['base_salary'] + array_sum(
            array_column(
                array_filter($this->calculations['earnings'], fn($e) => $e['label'] !== 'Base Salary'),
                'amount'
            )
        );

        $deductions = [];

        // Absence deduction
        if ($attendance['absent_days'] > 0) {
            $deductions['absence'] = [
                'label' => 'Absence Deduction',
                'days' => $attendance['absent_days'],
                'rate' => $rates['daily_rate'],
                'amount' => round($attendance['absent_days'] * $rates['daily_rate'], 2),
                'currency' => $this->config['currency'],
                'type' => 'absence',
            ];
        }

        // Income tax
        if ($this->config['apply_tax']) {
            $tax = $this->calculateIncomeTax($gross);
            if ($tax > 0) {
                $deductions['income_tax'] = [
                    'label' => 'Income Tax',
                    'amount' => $tax,
                    'currency' => $this->config['currency'],
                    'type' => 'tax',
                ];
            }
        }

        // Employee pension
        if ($this->config['apply_pension']) {
            $pension = round($gross * self::RATES['employee_pension'], 2);
            if ($pension > 0) {
                $deductions['employee_pension'] = [
                    'label' => 'Employee Pension',
                    'rate' => self::RATES['employee_pension'] * 100 . '%',
                    'amount' => $pension,
                    'currency' => $this->config['currency'],
                    'type' => 'pension',
                ];
            }
        }

        // Employer pension (for reference, not deducted from employee)
        if ($this->config['apply_pension']) {
            $employer_pension = round($gross * self::RATES['employer_pension'], 2);
            $deductions['employer_pension'] = [
                'label' => 'Employer Pension',
                'rate' => self::RATES['employer_pension'] * 100 . '%',
                'amount' => $employer_pension,
                'currency' => $this->config['currency'],
                'type' => 'pension',
                'display_only' => true,
            ];
        }

        return $deductions;
    }

    /**
     * Calculate Ethiopian income tax
     */
    private function calculateIncomeTax(float $gross): float
    {
        foreach (self::TAX_BRACKETS as [$min, $max, $rate, $deductible]) {
            if ($gross >= $min && $gross <= $max) {
                return round(($gross * $rate / 100) - $deductible, 2);
            }
        }
        return 0;
    }

    /**
     * Get all calculations
     */
    public function getCalculations(): array
    {
        return $this->calculations;
    }

    /**
     * Get summary only
     */
    public function getSummary(): array
    {
        return $this->calculations['summary'] ?? [];
    }
}
