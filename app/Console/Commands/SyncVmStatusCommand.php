<?php

namespace App\Console\Commands;

use App\Models\VirtualMachine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncVmStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vm:sync-status {--provider-id= : Sync only specific cloud provider}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync VM status with cloud providers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Starting VM status synchronization...');
            
            $query = VirtualMachine::query();
            
            if ($providerId = $this->option('provider-id')) {
                $query->where('cloud_provider_id', $providerId);
                $this->info("Syncing VMs for cloud provider ID: {$providerId}");
            }
            
            $vms = $query->get();
            $successCount = 0;
            $failureCount = 0;
            
            $this->withProgressBar($vms, function ($vm) use (&$successCount, &$failureCount) {
                try {
                    $success = $vm->syncWithCloud();
                    if ($success) {
                        $successCount++;
                    } else {
                        $failureCount++;
                        $this->line("Failed to sync VM: {$vm->name}");
                    }
                } catch (\Exception $e) {
                    $failureCount++;
                    $this->line("Error syncing VM {$vm->name}: {$e->getMessage()}");
                }
            });
            
            $this->newLine();
            $this->info("VM synchronization completed:");
            $this->info("- Successfully synced: {$successCount} VMs");
            $this->info("- Failed to sync: {$failureCount} VMs");
            
            Log::info('VM status synchronization completed', [
                'total_vms' => $vms->count(),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
            ]);
            
            return 0;
        } catch (\Exception $e) {
            $this->error("VM synchronization failed: {$e->getMessage()}");
            
            Log::error('VM status synchronization failed', [
                'error' => $e->getMessage(),
            ]);
            
            return 1;
        }
    }
}
