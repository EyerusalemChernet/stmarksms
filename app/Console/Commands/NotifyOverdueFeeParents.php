<?php

namespace App\Console\Commands;

use App\Services\ParentOverdueFeeNotifier;
use Illuminate\Console\Command;

class NotifyOverdueFeeParents extends Command
{
    protected $signature = 'fees:notify-overdue-parents';

    protected $description = 'Send parent reminders for unpaid fees past the due-date threshold';

    public function handle(): int
    {
        $stats = ParentOverdueFeeNotifier::run();

        $this->info("Messages sent: {$stats['messages']}, penalties applied: {$stats['penalties']}.");

        return self::SUCCESS;
    }
}
