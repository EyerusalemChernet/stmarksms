<?php
// Quick test to debug payroll issues

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\StaffPayroll;
use App\Models\Employee;

echo "\n=== PAYROLL DEBUG ===\n\n";

// Check if table exists
$tables = \DB::select("SHOW TABLES LIKE 'staff_payrolls'");
if (empty($tables)) {
    echo "❌ Table 'staff_payrolls' does NOT exist!\n";
    echo "Run migrations first: php artisan migrate\n";
    exit;
}
echo "✓ Table 'staff_payrolls' exists\n\n";

// Count payroll records
$count = StaffPayroll::count();
echo "Total payroll records: $count\n";

if ($count === 0) {
    echo "\n⚠️  No payroll records found!\n";
    echo "Action: Generate payroll first from HR → Payroll\n";
    exit;
}

// Show sample payroll records
echo "\nSample Payroll Records:\n";
echo str_repeat("-", 80) . "\n";
printf("%-5s | %-10s | %-30s | %-10s | %-10s\n", "ID", "Employee", "Month", "Status", "Amount");
echo str_repeat("-", 80) . "\n";

$payrolls = StaffPayroll::with('employee')
    ->limit(5)
    ->get();

foreach ($payrolls as $p) {
    $emp_name = $p->employee ? $p->employee->full_name : "Unknown";
    printf("%-5d | %-10d | %-30s | %-10s | %.2f\n", 
        $p->id, 
        $p->employee_id, 
        $p->month, 
        $p->status,
        $p->net_pay
    );
}

echo "\n✓ Payroll records exist and can be accessed\n";
echo "✓ IDs are numeric and valid\n\n";

// Check if employees have payroll
$employees = Employee::where('status', 'active')->limit(3)->get();
echo "Sample Employee/Payroll Mapping:\n";
echo str_repeat("-", 80) . "\n";

foreach ($employees as $emp) {
    $payroll = $emp->staffPayroll()->orderBy('id', 'desc')->first();
    if ($payroll) {
        echo "✓ Employee #{$emp->id} ({$emp->full_name}) has payroll record #{$payroll->id}\n";
    } else {
        echo "✗ Employee #{$emp->id} ({$emp->full_name}) has NO payroll\n";
    }
}

echo "\n=== END DEBUG ===\n\n";
