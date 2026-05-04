<?php

namespace App\Services\CloudProviders;

use Google\Cloud\Compute\V1\InstancesClient;
use Google\Cloud\Compute\V1\Instances;
use Google\Cloud\Billing\V1\CloudBillingClient;
use GuzzleHttp\Client;

class GCPProvider implements CloudProviderInterface
{
    private $client;
    private $billingClient;
    private $projectId;
    private $credentials;

    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
        $this->projectId = $credentials['project_id'] ?? null;
        $this->client = new InstancesClient([
            'credentials' => $credentials['service_account_key'] ?? null
        ]);
        $this->billingClient = new CloudBillingClient([
            'credentials' => $credentials['service_account_key'] ?? null
        ]);
    }

    public function authenticate(array $credentials): bool
    {
        try {
            $this->projectId = $credentials['project_id'];
            $this->client = new InstancesClient([
                'credentials' => $credentials['service_account_key']
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function listInstances(): array
    {
        try {
            $instances = [];
            $response = $this->client->list($this->projectId);
            
            foreach ($response as $instance) {
                $instances[] = [
                    'id' => $instance->getId(),
                    'name' => $instance->getName(),
                    'status' => $instance->getStatus(),
                    'zone' => $instance->getZone(),
                    'machineType' => $instance->getMachineType(),
                    'creationTimestamp' => $instance->getCreationTimestamp(),
                    'networkInterfaces' => $this->formatNetworkInterfaces($instance->getNetworkInterfaces()),
                    'disks' => $this->formatDisks($instance->getDisks()),
                    'tags' => $instance->getTags()->getItems(),
                    'labels' => $instance->getLabels(),
                ];
            }
            
            return $instances;
        } catch (\Exception $e) {
            throw new \Exception("Failed to list GCP instances: " . $e->getMessage());
        }
    }

    public function getInstance(string $instanceId): array
    {
        try {
            $instance = $this->client->get($this->projectId, $instanceId);
            
            return [
                'id' => $instance->getId(),
                'name' => $instance->getName(),
                'status' => $instance->getStatus(),
                'zone' => $instance->getZone(),
                'machineType' => $instance->getMachineType(),
                'creationTimestamp' => $instance->getCreationTimestamp(),
                'networkInterfaces' => $this->formatNetworkInterfaces($instance->getNetworkInterfaces()),
                'disks' => $this->formatDisks($instance->getDisks()),
                'tags' => $instance->getTags()->getItems(),
                'labels' => $instance->getLabels(),
                'metadata' => $instance->getMetadata()->getItems(),
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to get GCP instance: " . $e->getMessage());
        }
    }

    public function createInstance(array $config): array
    {
        try {
            $instance = new Instances();
            $instance->setName($config['name']);
            $instance->setMachineType($config['machine_type']);
            
            // Set network interfaces
            $networkInterface = new \Google\Cloud\Compute\V1\NetworkInterface();
            $networkInterface->setName($config['network'] ?? 'default');
            $instance->setNetworkInterfaces([$networkInterface]);
            
            // Set disks
            $disk = new \Google\Cloud\Compute\V1\AttachedDisk();
            $disk->setBoot(true);
            $disk->setAutoDelete(true);
            $disk->setInitializeParams([
                'sourceImage' => $config['source_image'] ?? 'projects/ubuntu-os-cloud/global/images/ubuntu-2004-focal-v20220101',
                'diskSizeGb' => $config['disk_size_gb'] ?? 20,
            ]);
            $instance->setDisks([$disk]);
            
            // Set labels and tags if provided
            if (isset($config['labels'])) {
                $instance->setLabels($config['labels']);
            }
            
            if (isset($config['tags'])) {
                $tags = new \Google\Cloud\Compute\V1\Tags();
                $tags->setItems($config['tags']);
                $instance->setTags($tags);
            }

            $operation = $this->client->insert($this->projectId, $instance, [
                'zone' => $config['zone'] ?? 'us-central1-a'
            ]);

            return [
                'operation_id' => $operation->getId(),
                'operation_type' => $operation->getOperationType(),
                'target_link' => $operation->getTargetLink(),
                'status' => $operation->getStatus(),
                'zone' => $config['zone'] ?? 'us-central1-a',
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to create GCP instance: " . $e->getMessage());
        }
    }

    public function startInstance(string $instanceId): bool
    {
        try {
            $zone = $this->getInstanceZone($instanceId);
            $operation = $this->client->start($this->projectId, $instanceId, $zone);
            return $operation->getStatus() === 'RUNNING';
        } catch (\Exception $e) {
            throw new \Exception("Failed to start GCP instance: " . $e->getMessage());
        }
    }

    public function stopInstance(string $instanceId): bool
    {
        try {
            $zone = $this->getInstanceZone($instanceId);
            $operation = $this->client->stop($this->projectId, $instanceId, $zone);
            return $operation->getStatus() === 'RUNNING';
        } catch (\Exception $e) {
            throw new \Exception("Failed to stop GCP instance: " . $e->getMessage());
        }
    }

    public function deleteInstance(string $instanceId): bool
    {
        try {
            $zone = $this->getInstanceZone($instanceId);
            $operation = $this->client->delete($this->projectId, $instanceId, $zone);
            return $operation->getStatus() === 'RUNNING';
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete GCP instance: " . $e->getMessage());
        }
    }

    public function getInstanceMetrics(string $instanceId): array
    {
        try {
            // Use Cloud Monitoring API to get metrics
            $client = new Client();
            $response = $client->get("https://monitoring.googleapis.com/v3/projects/{$this->projectId}/timeSeries", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ],
                'query' => [
                    'filter' => "metric.type=\"compute.googleapis.com/instance/cpu/utilization\" resource.type=\"gce_instance\" resource.label.instance_name=\"{$instanceId}\"",
                    'interval.endTime' => date('c'),
                    'interval.startTime' => date('c', strtotime('-1 hour')),
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return $this->formatMetrics($data);
        } catch (\Exception $e) {
            throw new \Exception("Failed to get GCP instance metrics: " . $e->getMessage());
        }
    }

    public function getBillingInfo(string $projectId): array
    {
        try {
            $billingAccounts = $this->billingClient->listBillingAccounts();
            $billingData = [];

            foreach ($billingAccounts as $account) {
                $billingData[] = [
                    'name' => $account->getDisplayName(),
                    'billing_id' => $account->getName(),
                    'open' => $account->getOpen(),
                ];
            }

            return $billingData;
        } catch (\Exception $e) {
            throw new \Exception("Failed to get GCP billing info: " . $e->getMessage());
        }
    }

    public function listRegions(): array
    {
        try {
            $client = new \Google\Cloud\Compute\V1\RegionsClient([
                'credentials' => $this->credentials['service_account_key']
            ]);
            
            $regions = [];
            $response = $client->list($this->projectId);
            
            foreach ($response as $region) {
                $regions[] = [
                    'name' => $region->getName(),
                    'description' => $region->getDescription(),
                    'status' => $region->getStatus(),
                    'zones' => $region->getZones(),
                ];
            }
            
            return $regions;
        } catch (\Exception $e) {
            throw new \Exception("Failed to list GCP regions: " . $e->getMessage());
        }
    }

    public function listInstanceTypes(): array
    {
        try {
            $client = new \Google\Cloud\Compute\V1\MachineTypesClient([
                'credentials' => $this->credentials['service_account_key']
            ]);
            
            $machineTypes = [];
            $response = $client->list($this->projectId, 'us-central1-a');
            
            foreach ($response as $machineType) {
                $machineTypes[] = [
                    'name' => $machineType->getName(),
                    'description' => $machineType->getDescription(),
                    'guestCpus' => $machineType->getGuestCpus(),
                    'memoryMb' => $machineType->getMemoryMb(),
                    'imageSpaceGb' => $machineType->getImageSpaceGb(),
                ];
            }
            
            return $machineTypes;
        } catch (\Exception $e) {
            throw new \Exception("Failed to list GCP instance types: " . $e->getMessage());
        }
    }

    public function getCostEstimate(array $config): array
    {
        // This would integrate with GCP Pricing API
        // For now, return estimated costs based on instance type
        $instanceType = $config['machine_type'] ?? 'e2-medium';
        $estimatedCosts = [
            'e2-medium' => ['hourly' => 0.041, 'monthly' => 29.52],
            'e2-standard-2' => ['hourly' => 0.082, 'monthly' => 59.04],
            'n1-standard-1' => ['hourly' => 0.0475, 'monthly' => 34.20],
            'n1-standard-2' => ['hourly' => 0.095, 'monthly' => 68.40],
        ];

        $baseCost = $estimatedCosts[$instanceType] ?? ['hourly' => 0.05, 'monthly' => 36];
        
        // Add storage costs
        $storageCost = ($config['disk_size_gb'] ?? 20) * 0.04; // $0.04 per GB per month
        
        return [
            'hourly' => $baseCost['hourly'],
            'monthly' => $baseCost['monthly'] + $storageCost,
            'currency' => 'USD',
            'breakdown' => [
                'compute' => $baseCost['monthly'],
                'storage' => $storageCost,
                'network' => 0,
            ],
        ];
    }

    private function formatNetworkInterfaces($interfaces): array
    {
        $formatted = [];
        foreach ($interfaces as $interface) {
            $formatted[] = [
                'network' => $interface->getNetwork(),
                'networkIP' => $interface->getNetworkIP(),
                'accessConfigs' => $interface->getAccessConfigs(),
            ];
        }
        return $formatted;
    }

    private function formatDisks($disks): array
    {
        $formatted = [];
        foreach ($disks as $disk) {
            $formatted[] = [
                'source' => $disk->getSource(),
                'boot' => $disk->getBoot(),
                'autoDelete' => $disk->getAutoDelete(),
                'deviceName' => $disk->getDeviceName(),
            ];
        }
        return $formatted;
    }

    private function formatMetrics($data): array
    {
        $metrics = [];
        foreach ($data['timeSeries'] ?? [] as $series) {
            $metrics[] = [
                'metric_type' => $series['metric']['type'],
                'value' => $series['points'][0]['value']['doubleValue'] ?? 0,
                'timestamp' => $series['points'][0]['interval']['endTime'],
            ];
        }
        return $metrics;
    }

    private function getInstanceZone(string $instanceId): string
    {
        $instance = $this->getInstance($instanceId);
        return basename($instance['zone']);
    }

    private function getAccessToken(): string
    {
        // Get OAuth2 token for API calls
        $auth = new \Google\Auth\ApplicationDefaultCredentials();
        return $auth->fetchAuthToken()['access_token'];
    }
}
