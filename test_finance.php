<?php
define('LARAVEL_START', microtime(true));
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;

$request = Request::create('/fees/pending', 'GET');

// Test 1: pendingList
try {
    $ctrl = $app->make(App\Http\Controllers\Finance\StudentFeeController::class);
    $response = $ctrl->pendingList($request);
    echo "pendingList: OK\n";
} catch (Exception $e) {
    echo "pendingList ERROR: " . $e->getMessage() . "\n  in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test 2: report
try {
    $response = $ctrl->report($request);
    echo "report: OK\n";
} catch (Exception $e) {
    echo "report ERROR: " . $e->getMessage() . "\n  in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test 3: structures
try {
    $response = $ctrl->structures($request);
    echo "structures: OK\n";
} catch (Exception $e) {
    echo "structures ERROR: " . $e->getMessage() . "\n  in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test 4: expense categories
try {
    $expCtrl = $app->make(App\Http\Controllers\Finance\ExpenseController::class);
    $response = $expCtrl->categories();
    echo "expense categories: OK\n";
} catch (Exception $e) {
    echo "expense categories ERROR: " . $e->getMessage() . "\n  in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test 5: Check what the actual view data looks like
try {
    $invoices = App\Models\StudentFeeInvoice::whereIn('status', ['unpaid','partial'])
        ->with(['student', 'fee_structure.category', 'fee_structure.my_class'])
        ->where('session', App\Helpers\Qs::getCurrentSession())
        ->get();
    echo "Pending invoices count: " . $invoices->count() . "\n";
    echo "Total pending: " . $invoices->sum('balance') . "\n";
    foreach ($invoices as $inv) {
        echo "  Invoice {$inv->id}: student=" . ($inv->student ? $inv->student->name : 'NULL')
            . " fee_structure=" . ($inv->fee_structure ? 'OK' : 'NULL')
            . " my_class=" . ($inv->fee_structure && $inv->fee_structure->my_class ? $inv->fee_structure->my_class->name : 'NULL')
            . "\n";
    }
} catch (Exception $e) {
    echo "Invoice query ERROR: " . $e->getMessage() . "\n";
}

// Test 6: Check FeeStructure foreign key issue
try {
    $structs = App\Models\FeeStructure::all();
    echo "\nFee structures:\n";
    foreach ($structs as $s) {
        echo "  id={$s->id} my_class_id={$s->my_class_id} fee_category_id={$s->fee_category_id} session={$s->session}\n";
    }
} catch (Exception $e) {
    echo "FeeStructure ERROR: " . $e->getMessage() . "\n";
}
