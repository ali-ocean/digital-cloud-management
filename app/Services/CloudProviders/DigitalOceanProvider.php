<?php

namespace App\Services\CloudProviders;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DigitalOceanProvider implements CloudProviderInterface
{
    private $client;
    private $apiKey;
    private $baseUrl = 'https://api.digitalocean.com/v2';

    public function __construct(array $credentials)
    {
        $this->apiKey = $credentials['api_key'];
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function authenticate(array $credentials): bool
    {
        try {
            $response = $this->client->get('account');
            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            return false;
        }
    }

    public function listInstances(): array
    {
        try {
            $response = $this->client->get('droplets');
            $data = json_decode($response->getBody(), true);
            
            $instances = [];
            foreach ($data['droplets'] as $droplet) {
                $instances[] = [
                    'id' => $droplet['id'],
                    'name' => $droplet['name'],
                    'status' => $droplet['status'],
                    'region' => $droplet['region'],
                    'size' => $droplet['size'],
                    'image' => $droplet['image'],
                    'created_at' => $droplet['created_at'],
                    'networks' => $droplet['networks'],
                    'kernel' => $droplet['kernel'],
                    'tags' => $droplet['tags'],
                    'features' => $droplet['features'],
                    'vpc_uuid' => $droplet['vpc_uuid'],
                ];
            }
            
            return $instances;
        } catch (RequestException $e) {
            throw new \Exception("Failed to list DigitalOcean instances: " . $e->getMessage());
        }
    }

    public function getInstance(string $instanceId): array
    {
        try {
            $response = $this->client->get("droplets/{$instanceId}");
            $droplet = json_decode($response->getBody(), true)['droplet'];
            
            return [
                'id' => $droplet['id'],
                'name' => $droplet['name'],
                'status' => $droplet['status'],
                'region' => $droplet['region'],
                'size' => $droplet['size'],
                'image' => $droplet['image'],
                'created_at' => $droplet['created_at'],
                'networks' => $droplet['networks'],
                'kernel' => $droplet['kernel'],
                'tags' => $droplet['tags'],
                'features' => $droplet['features'],
                'vpc_uuid' => $droplet['vpc_uuid'],
                'backups' => $droplet['backups'],
                'ipv4_address' => $droplet['networks']['v4'][0]['ip_address'] ?? null,
                'ipv6_address' => $droplet['networks']['v6'][0]['ip_address'] ?? null,
            ];
        } catch (RequestException $e) {
            throw new \Exception("Failed to get DigitalOcean instance: " . $e->getMessage());
        }
    }

    public function createInstance(array $config): array
    {
        try {
            $payload = [
                'name' => $config['name'],
                'region' => $config['region'] ?? 'nyc3',
                'size' => $config['size'] ?? 's-2vcpu-4gb',
                'image' => $config['image'] ?? 'ubuntu-20-04-x64',
                'backups' => $config['backups'] ?? false,
                'ipv6' => $config['ipv6'] ?? false,
                'monitoring' => $config['monitoring'] ?? false,
                'tags' => $config['tags'] ?? [],
            ];

            if (isset($config['ssh_keys'])) {
                $payload['ssh_keys'] = $config['ssh_keys'];
            }

            if (isset($config['vpc_uuid'])) {
                $payload['vpc_uuid'] = $config['vpc_uuid'];
            }

            $response = $this->client->post('droplets', [
                'json' => $payload
            ]);

            $data = json_decode($response->getBody(), true);
            
            return [
                'droplet_id' => $data['droplet']['id'],
                'action_id' => $data['droplet']['action_ids'][0] ?? null,
                'status' => 'creating',
                'region' => $config['region'] ?? 'nyc3',
                'size' => $config['size'] ?? 's-2vcpu-4gb',
            ];
        } catch (RequestException $e) {
            throw new \Exception("Failed to create DigitalOcean instance: " . $e->getMessage());
        }
    }

    public function startInstance(string $instanceId): bool
    {
        try {
            $response = $this->client->post("droplets/{$instanceId}/actions", [
                'json' => ['type' => 'power_on']
            ]);
            return $response->getStatusCode() === 201;
        } catch (RequestException $e) {
            throw new \Exception("Failed to start DigitalOcean instance: " . $e->getMessage());
        }
    }

    public function stopInstance(string $instanceId): bool
    {
        try {
            $response = $this->client->post("droplets/{$instanceId}/actions", [
                'json' => ['type' => 'power_off']
            ]);
            return $response->getStatusCode() === 201;
        } catch (RequestException $e) {
            throw new \Exception("Failed to stop DigitalOcean instance: " . $e->getMessage());
        }
    }

    public function deleteInstance(string $instanceId): bool
    {
        try {
            $response = $this->client->delete("droplets/{$instanceId}");
            return $response->getStatusCode() === 204;
        } catch (RequestException $e) {
            throw new \Exception("Failed to delete DigitalOcean instance: " . $e->getMessage());
        }
    }

    public function getInstanceMetrics(string $instanceId): array
    {
        try {
            // DigitalOcean monitoring API
            $response = $this->client->get("monitoring/metrics/droplet/cpu?host_id={$instanceId}");
            $cpuData = json_decode($response->getBody(), true);
            
            $response = $this->client->get("monitoring/metrics/droplet/memory?host_id={$instanceId}");
            $memoryData = json_decode($response->getBody(), true);
            
            $response = $this->client->get("monitoring/metrics/droplet/diskio?host_id={$instanceId}");
            $diskData = json_decode($response->getBody(), true);
            
            return [
                'cpu' => $this->formatMonitoringData($cpuData),
                'memory' => $this->formatMonitoringData($memoryData),
                'disk' => $this->formatMonitoringData($diskData),
            ];
        } catch (RequestException $e) {
            throw new \Exception("Failed to get DigitalOcean instance metrics: " . $e->getMessage());
        }
    }

    public function getBillingInfo(string $projectId): array
    {
        try {
            $response = $this->client->get('customers/my/invoices');
            $data = json_decode($response->getBody(), true);
            
            return [
                'invoices' => $data['invoices'] ?? [],
                'billing_history' => $data['billing_history'] ?? [],
                'balance' => $data['account_balance'] ?? 0,
            ];
        } catch (RequestException $e) {
            throw new \Exception("Failed to get DigitalOcean billing info: " . $e->getMessage());
        }
    }

    public function listRegions(): array
    {
        try {
            $response = $this->client->get('regions');
            $data = json_decode($response->getBody(), true);
            
            $regions = [];
            foreach ($data['regions'] as $region) {
                $regions[] = [
                    'slug' => $region['slug'],
                    'name' => $region['name'],
                    'sizes' => $region['sizes'],
                    'features' => $region['features'],
                    'available' => $region['available'],
                ];
            }
            
            return $regions;
        } catch (RequestException $e) {
            throw new \Exception("Failed to list DigitalOcean regions: " . $e->getMessage());
        }
    }

    public function listInstanceTypes(): array
    {
        try {
            $response = $this->client->get('sizes');
            $data = json_decode($response->getBody(), true);
            
            $sizes = [];
            foreach ($data['sizes'] as $size) {
                $sizes[] = [
                    'slug' => $size['slug'],
                    'memory' => $size['memory'],
                    'vcpus' => $size['vcpus'],
                    'disk' => $size['disk'],
                    'price_monthly' => $size['price_monthly'],
                    'price_hourly' => $size['price_hourly'],
                    'regions' => $size['regions'],
                    'available' => $size['available'],
                    'transfer' => $size['transfer'],
                ];
            }
            
            return $sizes;
        } catch (RequestException $e) {
            throw new \Exception("Failed to list DigitalOcean instance types: " . $e->getMessage());
        }
    }

    public function getCostEstimate(array $config): array
    {
        try {
            $instanceType = $config['size'] ?? 's-2vcpu-4gb';
            $response = $this->client->get('sizes');
            $data = json_decode($response->getBody(), true);
            
            $hourlyCost = 0;
            $monthlyCost = 0;
            
            foreach ($data['sizes'] as $size) {
                if ($size['slug'] === $instanceType) {
                    $hourlyCost = $size['price_hourly'];
                    $monthlyCost = $size['price_monthly'];
                    break;
                }
            }
            
            // Add backup costs if enabled
            $backupCost = 0;
            if ($config['backups'] ?? false) {
                $backupCost = $monthlyCost * 0.2; // 20% of droplet cost
            }
            
            // Add monitoring costs if enabled
            $monitoringCost = 0;
            if ($config['monitoring'] ?? false) {
                $monitoringCost = 3.50; // $3.50 per month
            }
            
            return [
                'hourly' => $hourlyCost,
                'monthly' => $monthlyCost + $backupCost + $monitoringCost,
                'currency' => 'USD',
                'breakdown' => [
                    'compute' => $monthlyCost,
                    'backups' => $backupCost,
                    'monitoring' => $monitoringCost,
                    'network' => 0,
                ],
            ];
        } catch (RequestException $e) {
            throw new \Exception("Failed to get DigitalOcean cost estimate: " . $e->getMessage());
        }
    }

    private function formatMonitoringData($data): array
    {
        $formatted = [];
        foreach ($data['data']['result'] ?? [] as $result) {
            $formatted[] = [
                'metric' => $result['metric'],
                'values' => $result['values'] ?? [],
                'timestamp' => date('c'),
            ];
        }
        return $formatted;
    }
}
