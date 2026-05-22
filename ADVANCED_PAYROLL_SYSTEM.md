# Advanced Payroll System - Implementation Guide

## Overview

A comprehensive, enterprise-grade payroll system with advanced features for:
- Complex payroll calculations
- Multi-currency support
- Advanced reporting and analytics
- Compliance and validation
- Audit trails and history

## Architecture

### Service Layer

The payroll system is built on three core services:

#### 1. **PayrollCalculator** (`app/Services/PayrollCalculator.php`)
Handles all payroll-related calculations with modular, composable logic.

**Features:**
- Base salary calculations
- Overtime pay (1.25x multiplier)
- Holiday pay (2.0x multiplier)
- Leave encashment (1.5x multiplier)
- Absence deductions
- Progressive tax calculations
- Pension contributions

**Usage:**
```php
$calculator = new PayrollCalculator($employee, '2024-01', [
    'currency' => 'ETB',
    'apply_tax' => true,
    'apply_pension' => true,
    'shift_hours' => 8,
]);

$calculations = $calculator->calculate();
$summary = $calculator->getSummary();
```

#### 2. **PayrollValidator** (`app/Services/PayrollValidator.php`)
Comprehensive validation for payroll operations with error and warning tracking.

**Validation Types:**
- Employee eligibility (active status, employment details, salary setup)
- Payroll data integrity (calculations, format, status)
- Workflow transitions (draft → approved → paid)
- Attendance completeness
- Period validation

**Usage:**
```php
$validator = new PayrollValidator();

if (!$validator->validateEmployeeEligibility($employee)) {
    $errors = $validator->getErrors();
}

if ($validator->validateCanApprove($payroll)) {
    // Safe to approve
}
```

#### 3. **PayrollReport** (`app/Services/PayrollReport.php`)
Advanced reporting with multiple report types and export formats.

**Report Types:**
- Summary Reports (totals, statistics, averages)
- Attendance Reports (presence, absences, leave)
- Department Reports (breakdown by department)
- Overtime Reports (detailed overtime analysis)
- Compliance Reports (processing status, completion percentage)
- Comparison Reports (month-over-month trends)

**Usage:**
```php
$report = new PayrollReport('2024-01');

$summary = $report->getSummaryReport();
$attendance = $report->getAttendanceReport();
$departments = $report->getDepartmentReport();
$overtime = $report->getOvertimeReport();
$compliance = $report->getComplianceReport();
$comparison = $report->getComparisonReport('2023-12');
```

## Enhanced StaffPayroll Model

### New Methods

#### Financial Getters
```php
$payroll->getGrossPayAttribute()        // Before deductions
$payroll->getStatutoryDeductionsAttribute() // Tax + Pension
$payroll->getEarningsBreakdown()        // Detailed earnings
$payroll->getDeductionsBreakdown()      // Detailed deductions
$payroll->getEffectiveTaxRate()         // As percentage
```

#### Status & Timeline
```php
$payroll->getProcessingTime()           // Time from creation to approval
$payroll->isOverdueForApproval()        // More than 7 days draft
$payroll->isOverdueForPayment()         // More than 30 days approved
$payroll->getStatusInfo()               // Full status with alerts
```

## Tax Brackets (Ethiopia)

```
Monthly Gross    Tax Rate    Cumulative Deductible
0 - 600 ETB      0%          0 ETB
601 - 1,650      10%         60 ETB
1,651 - 3,200    15%         142.50 ETB
3,201 - 5,250    20%         302.50 ETB
5,251 - 7,800    25%         565 ETB
7,801 - 10,900   30%         955 ETB
10,901+          35%         1,500 ETB
```

## Rates & Multipliers

| Component | Rate |
|-----------|------|
| Employee Pension | 7% |
| Employer Pension | 11% |
| Overtime Pay | 1.25x |
| Holiday Pay | 2.0x |
| Leave Encashment | 1.5x |

## Data Flow

### 1. Payroll Generation
```
Employee (active, with employment details)
    ↓
Attendance Summary (present, absent, leave, overtime)
    ↓
PayrollCalculator (calculates rates, earnings, deductions)
    ↓
Validation (integrity, eligibility)
    ↓
StaffPayroll Record (created with status: draft)
    ↓
PayrollItems (manual items: overtime, deductions, bonuses)
```

### 2. Payroll Workflow
```
Draft (editable)
    ↓ Approve
Approved (pending payment, read-only)
    ↓ Mark Paid
Paid (complete, finalized)
    ↓ (Optional) Revert to Draft
Draft (for corrections)
```

## Payroll Components

### Earnings
- **Base Salary**: Employee's configured monthly salary
- **Overtime Pay**: Hours × Hourly Rate × 1.25
- **Holiday Pay**: Days × Daily Rate × 2.0
- **Leave Encashment**: Days × Daily Rate × 1.5
- **Bonuses**: Manual additions by HR

### Deductions
- **Absence Deduction**: Days × Daily Rate
- **Income Tax**: Progressive brackets (Ethiopian standard)
- **Employee Pension**: 7% of gross
- **Other Deductions**: Manual deductions by HR

### Employer Costs (Not deducted from employee)
- **Employer Pension**: 11% of gross (employer responsibility)

## Calculation Formula

```
Gross Pay = Base Salary + Allowances
Tax = Progressive tax on Gross Pay
Employee Deductions = Income Tax + Employee Pension + Manual Deductions
Net Pay = Gross Pay - Employee Deductions

Employer Cost = Employer Pension (11% of Gross)
```

## Usage Examples

### Generate Payroll for Month
```php
$payrollService = app(PayrollService::class);
$attendanceService = app(AttendanceService::class);

$result = $payrollService->generateBulk('2024-01', $attendanceService);
// Returns: ['generated' => 45, 'skipped' => 3]
```

### Validate Employee
```php
$validator = new PayrollValidator();

if (!$validator->validateEmployeeEligibility($employee)) {
    foreach ($validator->getErrors() as $error) {
        echo "Error: $error";
    }
}
```

### Add Manual Item
```php
$payrollService->addItem(
    $payroll,
    'earning',
    'Performance Bonus',
    5000,
    'Q1 Performance Bonus'
);
```

### Get Reports
```php
$report = new PayrollReport('2024-01');

// All-in-one summary
$summary = $report->getSummaryReport();

// Department breakdown
$depts = $report->getDepartmentReport();
foreach ($depts as $dept => $data) {
    echo "{$dept}: {$data['total_net_pay']}";
}

// Overtime analysis
$ot = $report->getOvertimeReport();
echo "Total Overtime Hours: {$ot['total_overtime_hours']}";
echo "Total Overtime Pay: {$ot['total_overtime_pay']}";
```

### Approve & Pay Workflow
```php
$validator = new PayrollValidator();

// Check can approve
if ($validator->validateCanApprove($payroll)) {
    $payrollService->approve($payroll, auth()->id());
}

// Check can pay
if ($validator->validateCanPay($payroll)) {
    $payrollService->markPaid($payroll, auth()->id());
}

// If needed, revert
if ($validator->validateCanRevert($payroll)) {
    $payrollService->revertToDraft($payroll);
}
```

## Best Practices

### 1. Always Validate Before Operations
```php
$validator = new PayrollValidator();
if (!$validator->validatePayrollIntegrity($payroll)) {
    throw new Exception(implode(', ', $validator->getErrors()));
}
```

### 2. Use Transactions for Data Integrity
All critical operations use database transactions to ensure consistency.

### 3. Track Changes with Audit Logs
All payroll operations are automatically logged for compliance.

### 4. Handle Warnings & Errors Separately
```php
$validator = new PayrollValidator();
$validator->validatePayrollIntegrity($payroll);

if ($validator->hasErrors()) {
    // Block operation
}

if ($validator->hasWarnings()) {
    // Log warning, but allow operation
}
```

### 5. Use Calculator for Complex Scenarios
For custom payroll scenarios, use PayrollCalculator for flexible calculations.

## Database Schema

### staff_payrolls
- `id` - Primary key
- `employee_id` - Foreign key to employees
- `month` - Y-m format (2024-01)
- `base_salary` - Employee's salary
- `allowances` - Sum of earnings
- `deductions` - Sum of deductions
- `income_tax` - Ethiopian tax
- `employee_pension` - 7% deduction
- `employer_pension` - 11% employer cost
- `net_pay` - Final pay
- `status` - draft|approved|paid
- `approved_by` - User ID of approver
- `approved_at` - Approval timestamp
- `paid_at` - Payment timestamp
- `notes` - HR notes

### payroll_items
- `id` - Primary key
- `payroll_id` - Foreign key to staff_payrolls
- `type` - earning|deduction
- `label` - Item description
- `amount` - Monetary amount
- `note` - Additional notes

## Performance Considerations

### Indexes
Add indexes on frequently queried fields:
```sql
CREATE INDEX idx_payrolls_month ON staff_payrolls(month);
CREATE INDEX idx_payrolls_employee ON staff_payrolls(employee_id);
CREATE INDEX idx_payrolls_status ON staff_payrolls(status);
```

### Eager Loading
Always load relationships to avoid N+1 queries:
```php
StaffPayroll::with(['employee', 'approvedBy', 'items'])->get();
```

### Caching
Cache monthly summaries for reporting:
```php
Cache::rememberForever(
    "payroll_summary_{$month}",
    fn() => (new PayrollReport($month))->getSummaryReport()
);
```

## Security

### Authorization
- Only HR managers can generate payroll
- Only admins can approve payroll
- Only finance can mark as paid

### Data Validation
- All numeric values are validated
- Date ranges are validated
- Employee status is checked

### Audit Trail
- All operations logged to audit_logs
- Changes are tracked with timestamps
- User IDs recorded for accountability

## Migration Path

If upgrading from existing payroll system:

1. **Validate Data**: Run `PayrollValidator` on all existing records
2. **Audit Logs**: Ensure all payroll operations have audit entries
3. **Recalculate**: Use `recalculateFromItems()` for all draft payrolls
4. **Verify**: Run `getSummaryReport()` and compare with old system
5. **Archive**: Keep old payroll data as reference

## Troubleshooting

### Issue: Tax calculation seems off
**Solution**: Verify employee's tax bracket in `PayrollCalculator::TAX_BRACKETS`

### Issue: Overtime not calculating
**Solution**: Ensure attendance service is returning overtime hours correctly

### Issue: Deductions total doesn't match
**Solution**: Use `getDeductionsBreakdown()` to see component breakdown

### Issue: Payroll can't be approved
**Solution**: Run `$validator->validateCanApprove()` to see blocking issues

## Future Enhancements

- [ ] Payroll templates for different employee categories
- [ ] Advance salary functionality
- [ ] Salary revision management
- [ ] Integration with banking APIs for direct deposit
- [ ] Mobile app for payslip access
- [ ] Blockchain-based payment verification
- [ ] AI-based anomaly detection in payroll
- [ ] Multi-company support
- [ ] Customizable tax brackets per company

## Support & Maintenance

For issues or questions:
1. Check validation errors: `$validator->getErrors()`
2. Review audit logs: `AuditLog::where('module', 'hr')->latest()`
3. Use reports for analysis: `PayrollReport::*Report()`
4. Check database constraints and indexes
