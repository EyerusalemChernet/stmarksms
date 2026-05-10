<?php

namespace App\Console\Commands;

use App\Services\EthiopianHolidayService;
use Illuminate\Console\Command;

/**
 * Seed Ethiopian public holidays for one or more years.
 *
 * Usage:
 *   php artisan holidays:seed              # seeds current year
 *   php artisan holidays:seed --year=2025  # seeds specific year
 *   php artisan holidays:seed --year=2024 --year=2025  # multiple years
 */
class SeedEthiopianHolidays extends Command
{
    protected $signature   = 'holidays:seed {--year=* : Gregorian year(s) to seed (default: current year)}';
    protected $description = 'Seed Ethiopian public holidays into the database';

    public function handle(EthiopianHolidayService $service): int
    {
        $years = $this->option('year');
        if (empty($years)) {
            $years = [now()->year];
        }

        foreach ($years as $year) {
            $year  = (int) $year;
            $count = $service->seedYear($year);
            $this->info("Seeded {$count} holidays for {$year}.");

            // Show what was seeded
            $holidays = $service->getHolidaysForYear($year);
            $this->table(
                ['Date', 'Name', 'Type'],
                $holidays->map(fn($h) => [
                    $h['date']->format('d M Y'),
                    $h['name'],
                    ucfirst($h['type']),
                ])->toArray()
            );
        }

        return 0;
    }
}
