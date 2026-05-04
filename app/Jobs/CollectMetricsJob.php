<?php

namespace App\Jobs;

use App\Services\Monitoring\MonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CollectMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $retryAfter = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $monitoringService = app(MonitoringService::class);
            $metrics = $monitoringService->collectAllMetrics();
            
            Log::info('Metrics collection job completed', [
                'metrics_collected' => count($metrics),
                'job_id' => $this->job->getJobId(),
            ]);
        } catch (\Exception $e) {
            Log::error('Metrics collection job failed', [
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId(),
                'attempt' => $this->attempts(),
            ]);
            
            // Release the job to be retried
            $this->release(60);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Metrics collection job failed permanently', [
            'error' => $exception->getMessage(),
            'job_id' => $this->job->getJobId(),
            'attempts' => $this->attempts(),
        ]);
    }
}
