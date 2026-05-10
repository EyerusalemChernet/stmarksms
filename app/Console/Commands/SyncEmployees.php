<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\EmployeeProfileService;
use App\User;
use Illuminate\Console\Command;

/**
 * Backfill Employee records for all existing staff users that don't have one.
 *
 * Run once after deployment:
 *   php artisan hr:sync-employees
 *
 * Safe to run multiple times — skips already-linked users.
 */
class SyncEmployees extends Command
{
    protected $signature   = 'hr:sync-employees {--dry-run : Show what would be created without saving}';
    protected $description = 'Create missing Employee records for all unlinked staff users';

    public function handle(): int
    {
        $staffTypes = ['teacher', 'hr_manager', 'admin', 'super_admin'];

        $unlinked = User::whereIn('user_type', $staffTypes)
            ->whereNotIn('id', Employee::whereNotNull('user_id')->pluck('user_id'))
            ->get();

        if ($unlinked->isEmpty()) {
            $this->info('All staff users already have Employee records. Nothing to do.');
            return 0;
        }

        $this->info("Found {$unlinked->count()} unlinked staff user(s):");
        $this->table(
            ['ID', 'Name', 'Email', 'Type'],
            $unlinked->map(fn($u) => [$u->id, $u->name, $u->email, $u->user_type])->toArray()
        );

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no records created.');
            return 0;
        }

        $created = 0;
        foreach ($unlinked as $user) {
            $employee = EmployeeProfileService::createFromUser($user);
            if ($employee) {
                $this->line("  Created {$employee->employee_code} for {$user->name} ({$user->user_type})");
                $created++;
            }
        }

        $this->info("Done. Created {$created} Employee record(s).");
        return 0;
    }
}
