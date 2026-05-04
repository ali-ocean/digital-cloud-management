<?php

namespace App\Http\Controllers;

use App\Models\VirtualMachine;
use App\Models\Project;
use App\Models\CloudProvider;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class VirtualMachineController extends Controller
{
    /**
     * Display a listing of virtual machines.
     */
    public function index(Request $request): JsonResponse
    {
        $query = VirtualMachine::with(['project', 'cloudProvider', 'monitoringMetrics' => function ($query) {
            $query->latest()->take(10);
        }]);

        // Filter by project if provided
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by cloud provider if provided
        if ($request->has('cloud_provider_id')) {
            $query->where('cloud_provider_id', $request->cloud_provider_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $virtualMachines = $query->get();

        return response()->json([
            'success' => true,
            'data' => $virtualMachines,
        ]);
    }

    /**
     * Store a newly created virtual machine.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'project_id' => 'required|exists:projects,id',
                'cloud_provider_id' => 'required|exists:cloud_providers,id',
                'instance_type' => 'required|string',
                'region' => 'required|string',
                'zone' => 'nullable|string',
                'disk_size_gb' => 'sometimes|integer|min:10',
                'os_type' => 'required|string',
                'os_version' => 'required|string',
                'tags' => 'sometimes|array',
                'ssh_keys' => 'sometimes|array',
                'user_data' => 'sometimes|string',
            ]);

            $project = Project::findOrFail($validated['project_id']);
            $cloudProvider = CloudProvider::findOrFail($validated['cloud_provider_id']);

            // Verify project belongs to cloud provider
            if ($project->cloud_provider_id !== $cloudProvider->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project does not belong to the specified cloud provider',
                ], 422);
            }

            try {
                $service = $cloudProvider->getProviderService();
                
                // Get cost estimate
                $costEstimate = $service->getCostEstimate([
                    'instance_type' => $validated['instance_type'],
                    'disk_size_gb' => $validated['disk_size_gb'] ?? 20,
                ]);

                // Create VM in cloud
                $vmConfig = [
                    'name' => $validated['name'],
                    'instance_type' => $validated['instance_type'],
                    'region' => $validated['region'],
                    'zone' => $validated['zone'] ?? null,
                    'disk_size_gb' => $validated['disk_size_gb'] ?? 20,
                    'tags' => $validated['tags'] ?? [],
                ];

                if (isset($validated['ssh_keys'])) {
                    $vmConfig['ssh_keys'] = $validated['ssh_keys'];
                }

                if (isset($validated['user_data'])) {
                    $vmConfig['user_data'] = $validated['user_data'];
                }

                $cloudVm = $service->createInstance($vmConfig);

                // Create VM record in database
                $virtualMachine = VirtualMachine::create([
                    'name' => $validated['name'],
                    'project_id' => $validated['project_id'],
                    'cloud_provider_id' => $validated['cloud_provider_id'],
                    'cloud_vm_id' => $cloudVm['droplet_id'] ?? $cloudVm['server_id'] ?? $cloudVm['operation_id'],
                    'instance_type' => $validated['instance_type'],
                    'status' => 'creating',
                    'cpu_cores' => $this->getCpuCores($validated['instance_type']),
                    'ram_gb' => $this->getRamGb($validated['instance_type']),
                    'disk_gb' => $validated['disk_size_gb'] ?? 20,
                    'os_type' => $validated['os_type'],
                    'os_version' => $validated['os_version'],
                    'region' => $validated['region'],
                    'zone' => $validated['zone'] ?? null,
                    'tags' => $validated['tags'] ?? [],
                    'hourly_cost' => $costEstimate['hourly'],
                    'monthly_cost' => $costEstimate['monthly'],
                    'provisioned_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Virtual machine creation initiated',
                    'data' => $virtualMachine->load(['project', 'cloudProvider']),
                    'cost_estimate' => $costEstimate,
                ], 201);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create virtual machine: ' . $e->getMessage(),
                ], 500);
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
                'message' => 'Failed to create virtual machine: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified virtual machine.
     */
    public function show(VirtualMachine $virtualMachine): JsonResponse
    {
        $virtualMachine->load([
            'project',
            'cloudProvider',
            'monitoringMetrics' => function ($query) {
                $query->latest()->take(100);
            },
            'securityScans' => function ($query) {
                $query->latest()->take(10);
            },
            'backups' => function ($query) {
                $query->latest()->take(10);
            },
            'dnsRecords',
            'cicdPipelines',
        ]);

        return response()->json([
            'success' => true,
            'data' => $virtualMachine,
            'utilization_stats' => $virtualMachine->getUtilizationStats(),
        ]);
    }

    /**
     * Update the specified virtual machine.
     */
    public function update(Request $request, VirtualMachine $virtualMachine): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'tags' => 'sometimes|array',
                'is_monitored' => 'sometimes|boolean',
            ]);

            $virtualMachine->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Virtual machine updated successfully',
                'data' => $virtualMachine,
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
                'message' => 'Failed to update virtual machine: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified virtual machine.
     */
    public function destroy(VirtualMachine $virtualMachine): JsonResponse
    {
        try {
            // Delete VM from cloud provider first
            $service = $virtualMachine->getProviderService();
            $service->deleteInstance($virtualMachine->cloud_vm_id);

            // Delete from database
            $virtualMachine->delete();

            return response()->json([
                'success' => true,
                'message' => 'Virtual machine deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete virtual machine: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start the virtual machine.
     */
    public function start(VirtualMachine $virtualMachine): JsonResponse
    {
        try {
            $service = $virtualMachine->getProviderService();
            $success = $service->startInstance($virtualMachine->cloud_vm_id);

            if ($success) {
                $virtualMachine->update(['status' => 'starting']);
                return response()->json([
                    'success' => true,
                    'message' => 'Virtual machine start initiated',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to start virtual machine',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start virtual machine: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stop the virtual machine.
     */
    public function stop(VirtualMachine $virtualMachine): JsonResponse
    {
        try {
            $service = $virtualMachine->getProviderService();
            $success = $service->stopInstance($virtualMachine->cloud_vm_id);

            if ($success) {
                $virtualMachine->update(['status' => 'stopping']);
                return response()->json([
                    'success' => true,
                    'message' => 'Virtual machine stop initiated',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to stop virtual machine',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop virtual machine: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restart the virtual machine.
     */
    public function restart(VirtualMachine $virtualMachine): JsonResponse
    {
        try {
            $service = $virtualMachine->getProviderService();
            
            // Stop first, then start
            $stopSuccess = $service->stopInstance($virtualMachine->cloud_vm_id);
            if (!$stopSuccess) {
                throw new \Exception('Failed to stop instance for restart');
            }

            // Wait a moment and start
            sleep(2);
            $startSuccess = $service->startInstance($virtualMachine->cloud_vm_id);

            if ($startSuccess) {
                $virtualMachine->update(['status' => 'restarting']);
                return response()->json([
                    'success' => true,
                    'message' => 'Virtual machine restart initiated',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to restart virtual machine',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restart virtual machine: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync virtual machine status with cloud provider.
     */
    public function sync(VirtualMachine $virtualMachine): JsonResponse
    {
        try {
            $success = $virtualMachine->syncWithCloud();

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Virtual machine synced successfully' : 'Failed to sync virtual machine',
                'data' => $success ? $virtualMachine : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync virtual machine: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get virtual machine metrics.
     */
    public function getMetrics(VirtualMachine $virtualMachine, Request $request): JsonResponse
    {
        try {
            $hours = $request->get('hours', 24);
            $metrics = $virtualMachine->monitoringMetrics()
                ->where('recorded_at', '>=', now()->subHours($hours))
                ->orderBy('recorded_at', 'asc')
                ->get()
                ->groupBy('metric_type');

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'period_hours' => $hours,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get metrics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get virtual machine utilization statistics.
     */
    public function getUtilization(VirtualMachine $virtualMachine): JsonResponse
    {
        try {
            $utilizationStats = $virtualMachine->getUtilizationStats();

            return response()->json([
                'success' => true,
                'data' => $utilizationStats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get utilization stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle monitoring for virtual machine.
     */
    public function toggleMonitoring(VirtualMachine $virtualMachine, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'is_monitored' => 'required|boolean',
            ]);

            $virtualMachine->update(['is_monitored' => $validated['is_monitored']]);

            return response()->json([
                'success' => true,
                'message' => $validated['is_monitored'] ? 'Monitoring enabled' : 'Monitoring disabled',
                'data' => $virtualMachine,
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
                'message' => 'Failed to toggle monitoring: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getCpuCores(string $instanceType): int
    {
        // Simplified mapping - in production, this would be fetched from cloud provider API
        $cpuMap = [
            'e2-medium' => 2,
            'e2-standard-2' => 2,
            'n1-standard-1' => 1,
            'n1-standard-2' => 2,
            's-2vcpu-4gb' => 2,
            's2.large.2' => 2,
        ];

        return $cpuMap[$instanceType] ?? 2;
    }

    private function getRamGb(string $instanceType): int
    {
        // Simplified mapping - in production, this would be fetched from cloud provider API
        $ramMap = [
            'e2-medium' => 4,
            'e2-standard-2' => 8,
            'n1-standard-1' => 3.75,
            'n1-standard-2' => 7.5,
            's-2vcpu-4gb' => 4,
            's2.large.2' => 8,
        ];

        return $ramMap[$instanceType] ?? 4;
    }
}
