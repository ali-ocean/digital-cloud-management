<?php

namespace App\Console\Commands;

use App\Services\Monitoring\MonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:cleanup-metrics {--days=30 : Number of days to keep metrics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old monitoring metrics from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $daysToKeep = $this->option('days');
            
            $this->info("Starting metrics cleanup for the last {$daysToKeep} days...");
            
            $monitoringService = app(MonitoringService::class);
            $deletedCount = $monitoringService->cleanupOldMetrics($daysToKeep);
            
            $this->info("Successfully deleted {$deletedCount} old metric records.");
            
            Log::info('Metrics cleanup completed', [
                'deleted_count' => $deletedCount,
                'days_kept' => $daysToKeep,
            ]);
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to cleanup metrics: {$e->getMessage()}");
            
            Log::error('Metrics cleanup failed', [
                'error' => $e->getMessage(),
            ]);
            
            return 1;
        }
    }
}
