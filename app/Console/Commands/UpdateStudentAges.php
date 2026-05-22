<?php

namespace App\Console\Commands;

use App\Models\StudentRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateStudentAges extends Command
{
    protected $signature   = 'students:update-ages {--dry-run : Show what would be updated without saving}';
    protected $description = 'Recalculate and update the age of all active students from their date of birth.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $today  = Carbon::today();

        // Load all active (non-graduated) students who have a dob on their user record
        $records = StudentRecord::where('grad', 0)
            ->with('user')
            ->get()
            ->filter(fn($sr) => $sr->user && $sr->user->dob);

        if ($records->isEmpty()) {
            $this->info('No active students with a date of birth found.');
            return 0;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($records as $sr) {
            try {
                $dob    = Carbon::parse($sr->user->dob);
                $newAge = $dob->age; // Carbon calculates exact age from today

                if ((int) $sr->age === $newAge) {
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    $sr->update(['age' => $newAge]);
                }

                $this->line(sprintf(
                    '  %s %-30s DOB: %-12s  Age: %s → %s%s',
                    $sr->adm_no ?? '—',
                    $sr->user->name,
                    $sr->user->dob,
                    $sr->age ?? '?',
                    $newAge,
                    $dryRun ? ' [DRY RUN]' : ''
                ));

                $updated++;
            } catch (\Exception $e) {
                $this->warn("  Skipped {$sr->user->name}: {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Done. Updated: {$updated} | Already correct: {$skipped}");

        if ($dryRun) {
            $this->warn('Dry-run mode — no changes were saved.');
        }

        return 0;
    }
}
