<?php

namespace App\Console\Commands;

use App\Services\FeeUnificationService;
use Illuminate\Console\Command;

class MigrateLegacyFeesCommand extends Command
{
    protected $signature = 'fees:migrate-legacy';

    protected $description = 'Migrate legacy payment_records into student_fee_invoices';

    public function handle(FeeUnificationService $service): int
    {
        $this->info('Migrating legacy payment records...');
        $stats = $service->migrateAllLegacyRecords();
        $this->table(
            ['Migrated', 'Skipped', 'Errors'],
            [[$stats['migrated'], $stats['skipped'], $stats['errors']]]
        );

        return $stats['errors'] > 0 ? 1 : 0;
    }
}
