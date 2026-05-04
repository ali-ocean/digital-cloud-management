<?php

namespace App\Http\Controllers;

use App\Models\CloudProvider;
use App\Services\CloudProviders\CloudProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CloudProviderController extends Controller
{
    /**
     * Display a listing of cloud providers.
     */
    public function index(): JsonResponse
    {
        $providers = CloudProvider::with(['projects', 'virtualMachines'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $providers,
            'supported_providers' => CloudProviderFactory::getSupportedProviders(),
        ]);
    }

    /**
     * Store a newly created cloud provider.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:gcp,digitalocean,huawei',
                'credentials' => 'required|array',
                'region' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            // Validate credentials format
            $validation = CloudProviderFactory::validateCredentials($validated['type'], $validated['credentials']);
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'errors' => $validation['errors'],
                ], 422);
            }

            // Test connection
            try {
                $provider = CloudProviderFactory::create($validated['type'], $validated['credentials']);
                if (!$provider->authenticate($validated['credentials'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to authenticate with cloud provider',
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection test failed: ' . $e->getMessage(),
                ], 422);
            }

            $provider = CloudProvider::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Cloud provider added successfully',
                'data' => $provider,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add cloud provider: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified cloud provider.
     */
    public function show(CloudProvider $cloudProvider): JsonResponse
    {
        $cloudProvider->load(['projects.virtualMachines', 'virtualMachines', 'billings']);
        
        return response()->json([
            'success' => true,
            'data' => $cloudProvider,
        ]);
    }

    /**
     * Update the specified cloud provider.
     */
    public function update(Request $request, CloudProvider $cloudProvider): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'credentials' => 'sometimes|array',
                'region' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'is_active' => 'sometimes|boolean',
            ]);

            // If credentials are being updated, validate and test them
            if (isset($validated['credentials'])) {
                $validation = CloudProviderFactory::validateCredentials($cloudProvider->type, $validated['credentials']);
                if (!$validation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid credentials',
                        'errors' => $validation['errors'],
                    ], 422);
                }

                // Test connection
                try {
                    $provider = CloudProviderFactory::create($cloudProvider->type, $validated['credentials']);
                    if (!$provider->authenticate($validated['credentials'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to authenticate with cloud provider',
                        ], 422);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Connection test failed: ' . $e->getMessage(),
                    ], 422);
                }
            }

            $cloudProvider->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Cloud provider updated successfully',
                'data' => $cloudProvider,
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
                'message' => 'Failed to update cloud provider: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified cloud provider.
     */
    public function destroy(CloudProvider $cloudProvider): JsonResponse
    {
        try {
            if ($cloudProvider->projects()->count() > 0 || $cloudProvider->virtualMachines()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete cloud provider with associated projects or virtual machines',
                ], 422);
            }

            $cloudProvider->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cloud provider deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cloud provider: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test connection to cloud provider.
     */
    public function testConnection(CloudProvider $cloudProvider): JsonResponse
    {
        try {
            $isConnected = $cloudProvider->testConnection();
            
            return response()->json([
                'success' => true,
                'connected' => $isConnected,
                'message' => $isConnected ? 'Connection successful' : 'Connection failed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'connected' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get regions available for the cloud provider.
     */
    public function getRegions(CloudProvider $cloudProvider): JsonResponse
    {
        try {
            $service = $cloudProvider->getProviderService();
            $regions = $service->listRegions();
            
            return response()->json([
                'success' => true,
                'data' => $regions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch regions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get instance types available for the cloud provider.
     */
    public function getInstanceTypes(CloudProvider $cloudProvider): JsonResponse
    {
        try {
            $service = $cloudProvider->getProviderService();
            $instanceTypes = $service->listInstanceTypes();
            
            return response()->json([
                'success' => true,
                'data' => $instanceTypes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch instance types: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cost estimate for an instance.
     */
    public function getCostEstimate(Request $request, CloudProvider $cloudProvider): JsonResponse
    {
        try {
            $validated = $request->validate([
                'instance_type' => 'required|string',
                'disk_size_gb' => 'sometimes|integer|min:10',
                'backups' => 'sometimes|boolean',
                'monitoring' => 'sometimes|boolean',
            ]);

            $service = $cloudProvider->getProviderService();
            $estimate = $service->getCostEstimate($validated);
            
            return response()->json([
                'success' => true,
                'data' => $estimate,
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
                'message' => 'Failed to get cost estimate: ' . $e->getMessage(),
            ], 500);
        }
    }
}
