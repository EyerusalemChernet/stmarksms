<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StaffPayroll;

echo "=== TEST find() METHOD ===\n\n";

// Test different IDs
for ($id = 1; $id <= 5; $id++) {
    $payroll = StaffPayroll::find($id);
    if ($payroll) {
        echo "✓ ID $id: Found - Employee {$payroll->employee_id}, Month {$payroll->month}, Status {$payroll->status}\n";
    } else {
        echo "✗ ID $id: NOT FOUND\n";
    }
}

// Also test with() eager loading like the controller does
echo "\n=== WITH RELATIONS ===\n";
$payroll = StaffPayroll::with(['employee.employmentDetails', 'items', 'approvedBy'])->find(2);
if ($payroll) {
    echo "✓ Payroll 2 with relations: {$payroll->employee->full_name}\n";
} else {
    echo "✗ Payroll 2 with relations: NOT FOUND\n";
}
