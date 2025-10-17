<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ResourceUsageJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Resource usage monitoring - runs daily at 2 AM
        $schedule->call(function () {
            ResourceUsageJob::dispatch();
        })->dailyAt('02:00')
            ->name('resource-usage-check')
            ->description('Check resource usage (file count, disk usage, available inodes and space)')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
