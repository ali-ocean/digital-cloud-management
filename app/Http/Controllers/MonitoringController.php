<?php

namespace App\Http\Controllers;

use App\Models\VirtualMachine;
use App\Models\MonitoringMetric;
use App\Services\Monitoring\MonitoringService;
use App\Jobs\CollectMetricsJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MonitoringController extends Controller
{
    private $monitoringService;

    public function __construct(MonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Get monitoring dashboard data.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $monitoredVms = VirtualMachine::where('is_monitored', true)
                ->with(['project', 'cloudProvider'])
                ->get();

            $dashboardData = [
                'total_monitored_vms' => $monitoredVms->count(),
                'running_vms' => $monitoredVms->where('status', 'running')->count(),
                'stopped_vms' => $monitoredVms->where('status', 'stopped')->count(),
                'total_monthly_cost' => $monitoredVms->sum('monthly_cost'),
                'vms' => $monitoredVms->map(function ($vm) {
                    return [
                        'id' => $vm->id,
                        'name' => $vm->name,
                        'status' => $vm->status,
                        'project' => $vm->project->name,
                        'cloud_provider' => $vm->cloudProvider->name,
                        'monthly_cost' => $vm->monthly_cost,
                        'utilization_stats' => $vm->getUtilizationStats(),
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get monitoring dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get metrics for a specific VM.
     */
    public function getVmMetrics(VirtualMachine $virtualMachine, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'hours' => 'sometimes|integer|min:1|max:168', // Max 7 days
                'metric_type' => 'sometimes|string|in:cpu,memory,disk,network',
            ]);

            $hours = $validated['hours'] ?? 24;
            $metricType = $validated['metric_type'] ?? null;

            $query = $virtualMachine->monitoringMetrics()
                ->where('recorded_at', '>=', now()->subHours($hours))
                ->orderBy('recorded_at', 'asc');

            if ($metricType) {
                $query->where('metric_type', $metricType);
            }

            $metrics = $query->get()->groupBy('metric_name');

            return response()->json([
                'success' => true,
                'data' => [
                    'vm' => $virtualMachine->load(['project', 'cloudProvider']),
                    'metrics' => $metrics,
                    'period_hours' => $hours,
                    'utilization_stats' => $this->monitoringService->getUtilizationStats($virtualMachine, $hours),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get VM metrics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cost efficiency analysis for a VM.
     */
    public function getCostEfficiency(VirtualMachine $virtualMachine): JsonResponse
    {
        try {
            $analysis = $this->monitoringService->getCostEfficiencyAnalysis($virtualMachine);

            return response()->json([
                'success' => true,
                'data' => $analysis,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cost efficiency analysis: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cost efficiency analysis for all VMs.
     */
    public function getAllCostEfficiency(): JsonResponse
    {
        try {
            $monitoredVms = VirtualMachine::where('is_monitored', true)
                ->where('status', 'running')
                ->get();

            $analyses = $monitoredVms->map(function ($vm) {
                return $this->monitoringService->getCostEfficiencyAnalysis($vm);
            });

            // Calculate overall statistics
            $totalVms = $analyses->count();
            $totalCost = $analyses->sum('current_monthly_cost');
            $avgEfficiencyScore = $analyses->avg('efficiency_score');
            
            $recommendations = $analyses->pluck('recommendations')->flatten();
            $downsizeRecommendations = $recommendations->where('type', 'downsize')->count();
            $upscaleRecommendations = $recommendations->where('type', 'upscale')->count();
            $totalPotentialSavings = $recommendations->where('type', 'downsize')->sum('potential_savings');

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_vms' => $totalVms,
                        'total_monthly_cost' => $totalCost,
                        'avg_efficiency_score' => round($avgEfficiencyScore, 2),
                        'downsize_recommendations' => $downsizeRecommendations,
                        'upscale_recommendations' => $upscaleRecommendations,
                        'total_potential_savings' => $totalPotentialSavings,
                    ],
                    'vm_analyses' => $analyses,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cost efficiency analysis: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger manual metrics collection.
     */
    public function collectMetrics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'vm_id' => 'sometimes|exists:virtual_machines,id',
            ]);

            if (isset($validated['vm_id'])) {
                // Collect metrics for specific VM
                $vm = VirtualMachine::findOrFail($validated['vm_id']);
                $metrics = $this->monitoringService->collectMetrics($vm);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Metrics collected successfully',
                    'metrics_count' => count($metrics),
                    'vm_name' => $vm->name,
                ]);
            } else {
                // Collect metrics for all VMs
                CollectMetricsJob::dispatch();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Metrics collection job dispatched',
                ]);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to collect metrics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get monitoring metrics history for charts.
     */
    public function getMetricsHistory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'vm_id' => 'required|exists:virtual_machines,id',
                'metric_name' => 'required|string',
                'hours' => 'sometimes|integer|min:1|max:168',
                'interval' => 'sometimes|string|in:1m,5m,15m,1h',
            ]);

            $vm = VirtualMachine::findOrFail($validated['vm_id']);
            $hours = $validated['hours'] ?? 24;
            $interval = $validated['interval'] ?? '5m';

            $metrics = $vm->monitoringMetrics()
                ->where('metric_name', $validated['metric_name'])
                ->where('recorded_at', '>=', now()->subHours($hours))
                ->orderBy('recorded_at', 'asc')
                ->get();

            // Format data for charts
            $chartData = $metrics->map(function ($metric) {
                return [
                    'timestamp' => $metric->recorded_at->toISOString(),
                    'value' => $metric->value,
                    'unit' => $metric->unit,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'vm' => $vm->name,
                    'metric_name' => $validated['metric_name'],
                    'period_hours' => $hours,
                    'interval' => $interval,
                    'data_points' => $chartData,
                    'unit' => $metrics->first()?->unit,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get metrics history: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clean up old metrics.
     */
    public function cleanupMetrics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'days_to_keep' => 'sometimes|integer|min:7|max:365',
            ]);

            $daysToKeep = $validated['days_to_keep'] ?? 30;
            $deletedCount = $this->monitoringService->cleanupOldMetrics($daysToKeep);

            return response()->json([
                'success' => true,
                'message' => 'Old metrics cleaned up successfully',
                'deleted_count' => $deletedCount,
                'days_kept' => $daysToKeep,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup metrics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
