<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\StaffPayroll;
use Carbon\Carbon;

/**
 * PayrollValidator — Comprehensive payroll validation
 *
 * Validates:
 * - Employee eligibility for payroll
 * - Payroll data integrity
 * - Attendance data completeness
 * - Salary setup requirements
 * - Workflow state transitions
 */
class PayrollValidator
{
    private array $errors = [];
    private array $warnings = [];

    /**
     * Validate employee is eligible for payroll generation
     */
    public function validateEmployeeEligibility(Employee $employee): bool
    {
        $this->errors = [];

        // Check employee status
        if ($employee->status !== 'active') {
            $this->errors[] = "Employee is not active (Status: {$employee->status})";
            return false;
        }

        // Check employment details
        if (!$employee->employmentDetails) {
            $this->errors[] = "Employee has no employment details configured";
            return false;
        }

        // Check salary setup
        if ($employee->employmentDetails->salary <= 0) {
            $this->errors[] = "Employee salary is not set or is zero";
            return false;
        }

        // Check user assignment
        if (!$employee->user_id) {
            $this->errors[] = "Employee is not linked to a user account";
            return false;
        }

        return true;
    }

    /**
     * Validate payroll data integrity
     */
    public function validatePayrollIntegrity(StaffPayroll $payroll): bool
    {
        $this->errors = [];
        $this->warnings = [];

        // Check employee relationship
        if (!$payroll->employee) {
            $this->errors[] = "Payroll has no associated employee";
            return false;
        }

        // Check month format
        if (!preg_match('/^\d{4}-\d{2}$/', $payroll->month)) {
            $this->errors[] = "Invalid month format (expected YYYY-MM)";
            return false;
        }

        // Check base salary
        if ($payroll->base_salary < 0) {
            $this->errors[] = "Base salary cannot be negative";
            return false;
        }

        // Check calculations
        $expectedNet = $payroll->base_salary + $payroll->allowances - $payroll->deductions;
        if (abs($payroll->net_pay - $expectedNet) > 0.01) {
            $this->warnings[] = "Net pay calculation may be incorrect";
        }

        // Check status validity
        $validStatuses = ['draft', 'pending', 'approved', 'paid'];
        if (!in_array($payroll->status, $validStatuses)) {
            $this->errors[] = "Invalid payroll status: {$payroll->status}";
            return false;
        }

        return empty($this->errors);
    }

    /**
     * Validate payroll can be approved
     */
    public function validateCanApprove(StaffPayroll $payroll): bool
    {
        $this->errors = [];

        if ($payroll->status !== 'draft') {
            $this->errors[] = "Only draft payrolls can be approved (Current: {$payroll->status})";
            return false;
        }

        if (!$this->validatePayrollIntegrity($payroll)) {
            return false;
        }

        return true;
    }

    /**
     * Validate payroll can be marked as paid
     */
    public function validateCanPay(StaffPayroll $payroll): bool
    {
        $this->errors = [];

        if ($payroll->status !== 'approved') {
            $this->errors[] = "Only approved payrolls can be marked as paid (Current: {$payroll->status})";
            return false;
        }

        if (!$payroll->approved_at) {
            $this->errors[] = "Payroll must have an approval date";
            return false;
        }

        return true;
    }

    /**
     * Validate payroll can be reverted
     */
    public function validateCanRevert(StaffPayroll $payroll): bool
    {
        $this->errors = [];

        if ($payroll->status === 'paid') {
            $this->errors[] = "Paid payrolls cannot be reverted";
            return false;
        }

        return true;
    }

    /**
     * Validate attendance data completeness
     */
    public function validateAttendanceComplete(Employee $employee, string $month): bool
    {
        $this->errors = [];
        $this->warnings = [];

        // This would integrate with your attendance service
        // For now, just basic validation
        $start = Carbon::parse($month . '-01');
        $end = $start->copy()->endOfMonth();
        $totalDays = $end->day;

        // Check if attendance records exist
        // ... integrate with actual attendance data

        return true;
    }

    /**
     * Validate payroll period
     */
    public function validatePayrollPeriod(string $month): bool
    {
        $this->errors = [];

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->errors[] = "Invalid month format (expected YYYY-MM)";
            return false;
        }

        try {
            Carbon::parse($month . '-01');
        } catch (\Exception $e) {
            $this->errors[] = "Invalid month: {$month}";
            return false;
        }

        // Check if month is not in the future
        if (Carbon::parse($month . '-01')->isFuture()) {
            $this->warnings[] = "Generating payroll for a future month";
        }

        return true;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if there are any warnings
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Get all validation messages
     */
    public function getAllMessages(): array
    {
        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
