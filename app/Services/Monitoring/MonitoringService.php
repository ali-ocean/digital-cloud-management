<?php

namespace App\Services\Monitoring;

use App\Models\VirtualMachine;
use App\Models\MonitoringMetric;
use App\Services\CloudProviders\CloudProviderInterface;
use Illuminate\Support\Facades\Log;

class MonitoringService
{
    /**
     * Collect metrics for a virtual machine
     */
    public function collectMetrics(VirtualMachine $vm): array
    {
        if (!$vm->is_monitored) {
            return [];
        }

        try {
            $provider = $vm->getProviderService();
            $cloudMetrics = $provider->getInstanceMetrics($vm->cloud_vm_id);
            
            $collectedMetrics = [];
            
            foreach ($cloudMetrics as $metricType => $metricData) {
                $metrics = $this->processCloudMetrics($vm->id, $metricType, $metricData);
                $collectedMetrics = array_merge($collectedMetrics, $metrics);
            }
            
            // Store metrics in database
            MonitoringMetric::insert($collectedMetrics);
            
            Log::info('Metrics collected successfully', [
                'vm_id' => $vm->id,
                'vm_name' => $vm->name,
                'metrics_count' => count($collectedMetrics)
            ]);
            
            return $collectedMetrics;
        } catch (\Exception $e) {
            Log::error('Failed to collect metrics', [
                'vm_id' => $vm->id,
                'vm_name' => $vm->name,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Collect metrics for all monitored VMs
     */
    public function collectAllMetrics(): array
    {
        $monitoredVms = VirtualMachine::where('is_monitored', true)
            ->where('status', 'running')
            ->get();
        
        $totalMetrics = [];
        
        foreach ($monitoredVms as $vm) {
            $metrics = $this->collectMetrics($vm);
            $totalMetrics = array_merge($totalMetrics, $metrics);
        }
        
        Log::info('Bulk metrics collection completed', [
            'vms_processed' => $monitoredVms->count(),
            'total_metrics' => count($totalMetrics)
        ]);
        
        return $totalMetrics;
    }

    /**
     * Get utilization statistics for a VM
     */
    public function getUtilizationStats(VirtualMachine $vm, int $hours = 24): array
    {
        $metrics = $vm->monitoringMetrics()
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'desc')
            ->get()
            ->groupBy('metric_name');
        
        $stats = [];
        
        foreach ($metrics as $metricName => $metricValues) {
            $values = $metricValues->pluck('value')->toArray();
            
            if (!empty($values)) {
                $stats[$metricName] = [
                    'current' => $values[0],
                    'average' => array_sum($values) / count($values),
                    'min' => min($values),
                    'max' => max($values),
                    'unit' => $metricValues->first()->unit,
                    'data_points' => count($values),
                ];
            }
        }
        
        return $stats;
    }

    /**
     * Get cost efficiency analysis
     */
    public function getCostEfficiencyAnalysis(VirtualMachine $vm): array
    {
        $utilization = $this->getUtilizationStats($vm, 24);
        
        $cpuUtilization = $utilization['cpu_utilization']['average'] ?? 0;
        $memoryUtilization = $utilization['memory_utilization']['average'] ?? 0;
        
        $recommendations = [];
        
        // CPU efficiency analysis
        if ($cpuUtilization < 20) {
            $recommendations[] = [
                'type' => 'downsize',
                'component' => 'cpu',
                'current_utilization' => $cpuUtilization,
                'reason' => 'CPU utilization is very low, consider downsizing to save costs',
                'potential_savings' => $this->calculatePotentialSavings($vm, 'downsize'),
            ];
        } elseif ($cpuUtilization > 80) {
            $recommendations[] = [
                'type' => 'upscale',
                'component' => 'cpu',
                'current_utilization' => $cpuUtilization,
                'reason' => 'CPU utilization is high, consider upgrading for better performance',
                'potential_cost_increase' => $this->calculatePotentialCostIncrease($vm, 'upscale'),
            ];
        }
        
        // Memory efficiency analysis
        if ($memoryUtilization < 30) {
            $recommendations[] = [
                'type' => 'downsize',
                'component' => 'memory',
                'current_utilization' => $memoryUtilization,
                'reason' => 'Memory utilization is low, consider downsizing to save costs',
                'potential_savings' => $this->calculatePotentialSavings($vm, 'downsize_memory'),
            ];
        } elseif ($memoryUtilization > 85) {
            $recommendations[] = [
                'type' => 'upscale',
                'component' => 'memory',
                'current_utilization' => $memoryUtilization,
                'reason' => 'Memory utilization is high, consider upgrading for better performance',
                'potential_cost_increase' => $this->calculatePotentialCostIncrease($vm, 'upscale_memory'),
            ];
        }
        
        return [
            'vm_id' => $vm->id,
            'vm_name' => $vm->name,
            'current_monthly_cost' => $vm->monthly_cost,
            'utilization_stats' => $utilization,
            'recommendations' => $recommendations,
            'efficiency_score' => $this->calculateEfficiencyScore($cpuUtilization, $memoryUtilization),
        ];
    }

    /**
     * Process cloud provider metrics into our format
     */
    private function processCloudMetrics(int $vmId, string $metricType, array $metricData): array
    {
        $processedMetrics = [];
        $timestamp = now();
        
        switch ($metricType) {
            case 'cpu':
                $processedMetrics[] = [
                    'virtual_machine_id' => $vmId,
                    'metric_type' => 'cpu',
                    'metric_name' => 'cpu_utilization',
                    'value' => $this->extractCpuValue($metricData),
                    'unit' => 'percent',
                    'recorded_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                break;
                
            case 'memory':
                $processedMetrics[] = [
                    'virtual_machine_id' => $vmId,
                    'metric_type' => 'memory',
                    'metric_name' => 'memory_utilization',
                    'value' => $this->extractMemoryValue($metricData),
                    'unit' => 'percent',
                    'recorded_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                break;
                
            case 'disk':
                $processedMetrics[] = [
                    'virtual_machine_id' => $vmId,
                    'metric_type' => 'disk',
                    'metric_name' => 'disk_utilization',
                    'value' => $this->extractDiskValue($metricData),
                    'unit' => 'percent',
                    'recorded_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                break;
                
            case 'network':
                if (isset($metricData['network_in'])) {
                    $processedMetrics[] = [
                        'virtual_machine_id' => $vmId,
                        'metric_type' => 'network',
                        'metric_name' => 'network_in',
                        'value' => $this->extractNetworkValue($metricData['network_in']),
                        'unit' => 'mbps',
                        'recorded_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
                
                if (isset($metricData['network_out'])) {
                    $processedMetrics[] = [
                        'virtual_machine_id' => $vmId,
                        'metric_type' => 'network',
                        'metric_name' => 'network_out',
                        'value' => $this->extractNetworkValue($metricData['network_out']),
                        'unit' => 'mbps',
                        'recorded_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
                break;
        }
        
        return $processedMetrics;
    }

    /**
     * Extract CPU value from cloud provider metrics
     */
    private function extractCpuValue(array $metricData): float
    {
        // Handle different cloud provider formats
        if (isset($metricData[0]['value'])) {
            return (float) $metricData[0]['value'];
        }
        
        if (isset($metricData['cpu_util'])) {
            return (float) $metricData['cpu_util'];
        }
        
        return 0.0;
    }

    /**
     * Extract memory value from cloud provider metrics
     */
    private function extractMemoryValue(array $metricData): float
    {
        if (isset($metricData[0]['value'])) {
            return (float) $metricData[0]['value'];
        }
        
        if (isset($metricData['mem_util'])) {
            return (float) $metricData['mem_util'];
        }
        
        return 0.0;
    }

    /**
     * Extract disk value from cloud provider metrics
     */
    private function extractDiskValue(array $metricData): float
    {
        if (isset($metricData[0]['value'])) {
            return (float) $metricData[0]['value'];
        }
        
        return 0.0;
    }

    /**
     * Extract network value from cloud provider metrics
     */
    private function extractNetworkValue(array $networkData): float
    {
        if (isset($networkData[0]['value'])) {
            return (float) $networkData[0]['value'];
        }
        
        return 0.0;
    }

    /**
     * Calculate potential savings for downsizing
     */
    private function calculatePotentialSavings(VirtualMachine $vm, string $type): float
    {
        // Simplified calculation - in production, this would use actual pricing data
        $currentCost = $vm->monthly_cost;
        
        switch ($type) {
            case 'downsize':
                return $currentCost * 0.3; // 30% savings
            case 'downsize_memory':
                return $currentCost * 0.2; // 20% savings
            default:
                return 0.0;
        }
    }

    /**
     * Calculate potential cost increase for upscaling
     */
    private function calculatePotentialCostIncrease(VirtualMachine $vm, string $type): float
    {
        $currentCost = $vm->monthly_cost;
        
        switch ($type) {
            case 'upscale':
                return $currentCost * 0.5; // 50% increase
            case 'upscale_memory':
                return $currentCost * 0.3; // 30% increase
            default:
                return 0.0;
        }
    }

    /**
     * Calculate efficiency score (0-100)
     */
    private function calculateEfficiencyScore(float $cpuUtilization, float $memoryUtilization): int
    {
        // Optimal range is 40-70% utilization
        $cpuScore = $this->calculateComponentScore($cpuUtilization);
        $memoryScore = $this->calculateComponentScore($memoryUtilization);
        
        return (int) (($cpuScore + $memoryScore) / 2);
    }

    /**
     * Calculate component efficiency score
     */
    private function calculateComponentScore(float $utilization): int
    {
        if ($utilization >= 40 && $utilization <= 70) {
            return 100; // Optimal
        } elseif ($utilization >= 30 && $utilization < 40) {
            return 80; // Good
        } elseif ($utilization > 70 && $utilization <= 80) {
            return 75; // Acceptable
        } elseif ($utilization >= 20 && $utilization < 30) {
            return 60; // Fair
        } elseif ($utilization > 80 && $utilization <= 90) {
            return 50; // Poor
        } else {
            return 25; // Very poor
        }
    }

    /**
     * Clean up old metrics to prevent database bloat
     */
    public function cleanupOldMetrics(int $daysToKeep = 30): int
    {
        $deletedCount = MonitoringMetric::where('recorded_at', '<', now()->subDays($daysToKeep))
            ->delete();
        
        Log::info('Old metrics cleaned up', [
            'deleted_count' => $deletedCount,
            'days_kept' => $daysToKeep
        ]);
        
        return $deletedCount;
    }
}
