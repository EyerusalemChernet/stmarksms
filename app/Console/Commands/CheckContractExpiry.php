<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\EmploymentDetails;
use Illuminate\Console\Command;

/**
 * Check for expiring and expired contracts and log alerts.
 *
 * Usage:
 *   php artisan contracts:check              # check with default 30-day window
 *   php artisan contracts:check --days=60    # check within 60 days
 *
 * Schedule in Kernel.php:
 *   $schedule->command('contracts:check')->weeklyOn(1, '08:00');
 */
class CheckContractExpiry extends Command
{
    protected $signature   = 'contracts:check {--days=30 : Days ahead to check for expiring contracts}';
    protected $description = 'Check for expiring and expired staff contracts and log alerts';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        // ── Expired contracts ────────────────────────────────────────────────
        $expired = EmploymentDetails::with('employee')
            ->whereHas('employee', fn($q) => $q->where('status', 'active'))
            ->whereNotNull('contract_end_date')
            ->where('contract_end_date', '<', now())
            ->get();

        if ($expired->count() > 0) {
            $this->error("EXPIRED CONTRACTS ({$expired->count()}):");
            foreach ($expired as $ed) {
                $emp  = $ed->employee;
                $name = $emp ? $emp->full_name : 'Unknown';
                $code = $emp ? $emp->employee_code : '—';
                $date = $ed->contract_end_date->format('d M Y');
                $daysAgo = abs($ed->daysUntilExpiry());
                $this->line("  ✗ {$name} ({$code}) — expired {$date} ({$daysAgo} days ago)");
                AuditLog::log('warning', 'hr', "Contract EXPIRED: {$code} ({$name}) — expired {$date}");
            }
        } else {
            $this->info('No expired contracts.');
        }

        // ── Expiring soon ────────────────────────────────────────────────────
        $expiring = EmploymentDetails::with('employee')
            ->whereHas('employee', fn($q) => $q->where('status', 'active'))
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [now(), now()->addDays($days)])
            ->orderBy('contract_end_date')
            ->get();

        if ($expiring->count() > 0) {
            $this->warn("EXPIRING WITHIN {$days} DAYS ({$expiring->count()}):");
            foreach ($expiring as $ed) {
                $emp      = $ed->employee;
                $name     = $emp ? $emp->full_name : 'Unknown';
                $code     = $emp ? $emp->employee_code : '—';
                $date     = $ed->contract_end_date->format('d M Y');
                $daysLeft = $ed->daysUntilExpiry();
                $this->line("  ! {$name} ({$code}) — expires {$date} ({$daysLeft} days left)");
                AuditLog::log('warning', 'hr', "Contract expiring soon: {$code} ({$name}) — expires {$date} in {$daysLeft} days");
            }
        } else {
            $this->info("No contracts expiring within {$days} days.");
        }

        $this->newLine();
        $this->info('Summary: '.$expired->count().' expired, '.$expiring->count()." expiring within {$days} days.");
        $this->info('Visit /hr/contracts to review and renew.');

        return 0;
    }
}
