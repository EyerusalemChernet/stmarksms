<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StaffPayroll;

echo "=== TESTING ROUTE GENERATION ===\n\n";

$payroll = StaffPayroll::first();
if($payroll) {
    echo "Payroll ID: {$payroll->id}\n";
    echo "Payroll ID type: " . gettype($payroll->id) . "\n";
    echo "Payroll ID is int: " . ($payroll->id === (int)$payroll->id ? 'yes' : 'no') . "\n";
    
    $url = route('hr.payroll.edit', $payroll->id);
    echo "Generated URL: $url\n";
    
    // Also test with casting
    $url2 = route('hr.payroll.edit', (int)$payroll->id);
    echo "Generated URL (with cast): $url2\n";
} else {
    echo "No payroll found\n";
}
