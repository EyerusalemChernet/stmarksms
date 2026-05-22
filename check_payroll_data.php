<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PAYROLL DATA CHECK ===\n\n";

$payrolls = DB::table('staff_payrolls')->select('id', 'employee_id', 'month', 'status', 'net_pay')->limit(5)->get();

if($payrolls->isNotEmpty()) {
    foreach($payrolls as $p) {
        echo "Payroll ID: {$p->id}\n";
        echo "  Employee ID: {$p->employee_id}\n";
        echo "  Month: {$p->month}\n";
        echo "  Status: {$p->status}\n";
        echo "  Net Pay: {$p->net_pay}\n\n";
    }
} else {
    echo "No payroll records\n";
}

// Check if employee_id column exists
$columns = DB::getSchemaBuilder()->getColumnListing('staff_payrolls');
echo "\nColumns in staff_payrolls table:\n";
echo implode(", ", $columns) . "\n";
