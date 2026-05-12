<?php

namespace App\Console\Commands;

use App\Models\PayrollItem;
use App\Models\StaffPayroll;
use App\Services\PayrollService;
use Illuminate\Console\Command;

/**
 * One-time cleanup command.
 *
 * Removes statutory items (Basic Salary, Income Tax, Employee Pension,
 * Employer Pension) that were incorrectly stored as PayrollItems in old data,
 * then recalculates all draft payrolls so totals are correct.
 *
 * Run once: php artisan payroll:clean-items
 */
class CleanPayrollItems extends Command
{
    protected $signature   = 'payroll:clean-items';
    protected $description = 'Remove statutory items from payroll_items and recalculate draft payrolls';

    public function handle(PayrollService $service): int
    {
        // Labels that should NEVER be in payroll_items
        $statutory = [
            'Basic Salary',
            'Income Tax',
            'Employee Pension (7%)',
            'Employer Pension (11%)',
        ];

        $deleted = PayrollItem::whereIn('label', $statutory)->count();
        PayrollItem::whereIn('label', $statutory)->delete();
        $this->info("Deleted {$deleted} statutory payroll item row(s).");

        // Recalculate all draft payrolls
        $drafts = StaffPayroll::where('status', 'draft')->get();
        $this->info("Recalculating {$drafts->count()} draft payroll(s)...");

        foreach ($drafts as $payroll) {
            $service->recalculateFromItems($payroll);
            $name = $payroll->employee ? $payroll->employee->full_name : 'unknown';
            $this->line("  Payroll #{$payroll->id} — {$name} ({$payroll->month})");
        }

        $this->info('Done. All draft payrolls recalculated with correct formula.');
        return 0;
    }
}
