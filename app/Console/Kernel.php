<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\GenerateAcademicCalendar::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
        {
            // Auto-generate next academic year every September 1st at 06:00 AM
            $schedule->command('calendar:generate', [date('Y')])
                     ->yearlyOn(9, 1, '06:00')
                     ->withoutOverlapping()
                     ->runInBackground()
                     ->appendOutputTo(storage_path('logs/calendar-generator.log'));
        }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
