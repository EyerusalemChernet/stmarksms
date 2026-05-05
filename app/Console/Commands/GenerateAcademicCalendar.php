<?php

namespace App\Console\Commands;

use App\Services\AcademicCalendarGeneratorService;
use Illuminate\Console\Command;

class GenerateAcademicCalendar extends Command
{
    protected $signature   = 'calendar:generate {year? : Gregorian start year (e.g. 2025)}';
    protected $description = 'Auto-generate the Ethiopian academic calendar for a given year';

    public function handle(AcademicCalendarGeneratorService $generator): int
    {
        $year = (int) ($this->argument('year') ?? date('Y'));

        $this->info("Generating academic calendar for {$year}/".($year+1)."...");

        try {
            $academicYear = $generator->generateAcademicYear($year);

            $events    = count($generator->getGeneratedEvents());
            $holidays  = count($generator->getHolidayDates());
            $conflicts = count($generator->getConflicts());

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Events generated',    $events],
                    ['Holidays imported',   $holidays],
                    ['Conflicts resolved',  $conflicts],
                ]
            );

            $this->info("✓ Academic year '{$academicYear->name}' published successfully.");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Generation failed: '.$e->getMessage());
            return Command::FAILURE;
        }
    }
}
