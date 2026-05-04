<?php

namespace App\Console;

use App\Jobs\CollectMetricsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Collect metrics every 5 minutes for all monitored VMs
        $schedule->job(new CollectMetricsJob())
            ->everyFiveMinutes()
            ->description('Collect metrics from all monitored VMs')
            ->withoutOverlapping();

        // Clean up old metrics daily at 2 AM
        $schedule->command('monitoring:cleanup-metrics')
            ->dailyAt('02:00')
            ->description('Clean up old monitoring metrics');

        // Sync VM status with cloud providers every 10 minutes
        $schedule->command('vm:sync-status')
            ->everyTenMinutes()
            ->description('Sync VM status with cloud providers')
            ->withoutOverlapping();

        // Generate cost efficiency report weekly
        $schedule->command('monitoring:cost-efficiency-report')
            ->weekly()
            ->mondays()
            ->at('09:00')
            ->description('Generate weekly cost efficiency report');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
