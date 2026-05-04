<?php

namespace App\Services\CloudProviders;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class HuaweiProvider implements CloudProviderInterface
{
    private $client;
    private $credentials;
    private $token;
    private $baseUrl = 'https://ecs.{region}.myhuaweicloud.com/v1';

    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
        $this->baseUrl = str_replace('{region}', $credentials['region'] ?? 'eu-west-0', $this->baseUrl);
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function authenticate(array $credentials): bool
    {
        try {
            $this->token = $this->getAuthToken($credentials);
            return !empty($this->token);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function listInstances(): array
    {
        try {
            $this->ensureAuthenticated();
            $response = $this->client->get('cloudservers/detail', [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            
            $instances = [];
            foreach ($data['servers'] ?? [] as $server) {
                $instances[] = [
                    'id' => $server['id'],
                    'name' => $server['name'],
                    'status' => $server['status'],
                    'availability_zone' => $server['OS-EXT-AZ:availability_zone'],
                    'flavor' => $server['flavor'],
                    'image' => $server['image'],
                    'created' => $server['created'],
                    'addresses' => $server['addresses'],
                    'metadata' => $server['metadata'] ?? [],
                    'security_groups' => $server['security_groups'] ?? [],
                    'tags' => $server['tags'] ?? [],
                ];
            }
            
            return $instances;
        } catch (RequestException $e) {
            throw new \Exception("Failed to list Huawei instances: " . $e->getMessage());
        }
    }

    public function getInstance(string $instanceId): array
    {
        try {
            $this->ensureAuthenticated();
            $response = $this->client->get("cloudservers/{$instanceId}", [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
            ]);
            $server = json_decode($response->getBody(), true)['server'];
            
            return [
                'id' => $server['id'],
                'name' => $server['name'],
                'status' => $server['status'],
                'availability_zone' => $server['OS-EXT-AZ:availability_zone'],
                'flavor' => $server['flavor'],
                'image' => $server['image'],
                'created' => $server['created'],
                'addresses' => $server['addresses'],
                'metadata' => $server['metadata'] ?? [],
                'security_groups' => $server['security_groups'] ?? [],
                'tags' => $server['tags'] ?? [],
                'key_name' => $server['key_name'] ?? null,
                'user_data' => $server['user_data'] ?? null,
            ];
        } catch (RequestException $e) {
            throw new \Exception("Failed to get Huawei instance: " . $e->getMessage());
        }
    }

    public function createInstance(array $config): array
    {
        try {
            $this->ensureAuthenticated();
            
            $payload = [
                'server' => [
                    'name' => $config['name'],
                    'flavorRef' => $config['flavor'] ?? 's2.large.2',
                    'imageRef' => $config['image'] ?? $this->getDefaultImage(),
                    'availability_zone' => $config['availability_zone'] ?? 'eu-west-0a',
                    'root_volume' => [
                        'volumetype' => 'SATA',
                        'size' => $config['disk_size_gb'] ?? 40,
                    ],
                    'security_groups' => $config['security_groups'] ?? [['name' => 'default']],
                    'nics' => $config['nics'] ?? [[
                        'subnet_id' => $config['subnet_id'] ?? $this->getDefaultSubnet(),
                    ]],
                    'key_name' => $config['key_name'] ?? null,
                    'user_data' => $config['user_data'] ?? null,
                    'adminPass' => $config['admin_password'] ?? null,
                ],
            ];

            if (isset($config['tags'])) {
                $payload['server']['tags'] = $config['tags'];
            }

            $response = $this->client->post('cloudservers', [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody(), true);
            
            return [
                'server_id' => $data['server']['id'],
                'job_id' => $data['job_id'] ?? null,
                'status' => 'creating',
                'availability_zone' => $config['availability_zone'] ?? 'eu-west-0a',
                'flavor' => $config['flavor'] ?? 's2.large.2',
            ];
        } catch (RequestException $e) {
            throw new \Exception("Failed to create Huawei instance: " . $e->getMessage());
        }
    }

    public function startInstance(string $instanceId): bool
    {
        try {
            $this->ensureAuthenticated();
            $response = $this->client->post("cloudservers/{$instanceId}/action", [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
                'json' => [
                    'os-start' => null,
                ],
            ]);
            return $response->getStatusCode() === 202;
        } catch (RequestException $e) {
            throw new \Exception("Failed to start Huawei instance: " . $e->getMessage());
        }
    }

    public function stopInstance(string $instanceId): bool
    {
        try {
            $this->ensureAuthenticated();
            $response = $this->client->post("cloudservers/{$instanceId}/action", [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
                'json' => [
                    'os-stop' => null,
                ],
            ]);
            return $response->getStatusCode() === 202;
        } catch (RequestException $e) {
            throw new \Exception("Failed to stop Huawei instance: " . $e->getMessage());
        }
    }

    public function deleteInstance(string $instanceId): bool
    {
        try {
            $this->ensureAuthenticated();
            $response = $this->client->delete("cloudservers/{$instanceId}", [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
            ]);
            return $response->getStatusCode() === 204;
        } catch (RequestException $e) {
            throw new \Exception("Failed to delete Huawei instance: " . $e->getMessage());
        }
    }

    public function getInstanceMetrics(string $instanceId): array
    {
        try {
            $this->ensureAuthenticated();
            
            // Huawei Cloud Eye service for monitoring
            $baseUrl = str_replace('ecs', 'ces', $this->baseUrl);
            $client = new Client(['base_uri' => $baseUrl]);
            
            $endTime = date('c');
            $startTime = date('c', strtotime('-1 hour'));
            
            $response = $client->get('metric-data', [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
                'query' => [
                    'namespace' => 'SYS.ECS',
                    'metric_name' => 'cpu_util',
                    'dim.0' => "instance_id,{$instanceId}",
                    'from' => $startTime,
                    'to' => $endTime,
                    'period' => '1',
                ],
            ]);
            
            $cpuData = json_decode($response->getBody(), true);
            
            // Get memory metrics
            $response = $client->get('metric-data', [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
                'query' => [
                    'namespace' => 'SYS.ECS',
                    'metric_name' => 'mem_util',
                    'dim.0' => "instance_id,{$instanceId}",
                    'from' => $startTime,
                    'to' => $endTime,
                    'period' => '1',
                ],
            ]);
            
            $memoryData = json_decode($response->getBody(), true);
            
            return [
                'cpu' => $this->formatHuaweiMetrics($cpuData),
                'memory' => $this->formatHuaweiMetrics($memoryData),
            ];
        } catch (RequestException $e) {
            throw new \Exception("Failed to get Huawei instance metrics: " . $e->getMessage());
        }
    }

    public function getBillingInfo(string $projectId): array
    {
        try {
            $this->ensureAuthenticated();
            
            // Huawei Billing API
            $baseUrl = str_replace('ecs', 'bss', $this->baseUrl);
            $client = new Client(['base_uri' => $baseUrl]);
            
            $response = $client->get('orders/customer-orders', [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            return [
                'orders' => $data['orders'] ?? [],
                'customer_id' => $data['customer_id'] ?? null,
                'total_amount' => $data['total_amount'] ?? 0,
            ];
        } catch (RequestException $e) {
            throw new \Exception("Failed to get Huawei billing info: " . $e->getMessage());
        }
    }

    public function listRegions(): array
    {
        try {
            $this->ensureAuthenticated();
            $response = $this->client->get('cloudservers/availability-zones', [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            
            $regions = [];
            foreach ($data['availabilityZoneInfo'] ?? [] as $zone) {
                $regions[] = [
                    'zoneName' => $zone['zoneName'],
                    'zoneState' => $zone['zoneState'],
                    'available' => $zone['zoneState']['available'] ?? false,
                ];
            }
            
            return $regions;
        } catch (RequestException $e) {
            throw new \Exception("Failed to list Huawei regions: " . $e->getMessage());
        }
    }

    public function listInstanceTypes(): array
    {
        try {
            $this->ensureAuthenticated();
            $response = $this->client->get('cloudservers/flavors', [
                'headers' => [
                    'X-Auth-Token' => $this->token,
                ],
            ]);
            $data = json_decode($response->getBody(), true);
            
            $flavors = [];
            foreach ($data['flavors'] ?? [] as $flavor) {
                $flavors[] = [
                    'id' => $flavor['id'],
                    'name' => $flavor['name'],
                    'vcpus' => $flavor['vcpus'],
                    'ram' => $flavor['ram'],
                    'disk' => $flavor['disk'],
                    'os-flavor-access:is_public' => $flavor['os-flavor-access:is_public'] ?? true,
                ];
            }
            
            return $flavors;
        } catch (RequestException $e) {
            throw new \Exception("Failed to list Huawei instance types: " . $e->getMessage());
        }
    }

    public function getCostEstimate(array $config): array
    {
        // Huawei pricing would typically be fetched from their pricing API
        // For now, return estimated costs based on instance type
        $instanceType = $config['flavor'] ?? 's2.large.2';
        $estimatedCosts = [
            's2.large.2' => ['hourly' => 0.08, 'monthly' => 57.60],
            's2.xlarge.2' => ['hourly' => 0.16, 'monthly' => 115.20],
            's2.2xlarge.2' => ['hourly' => 0.32, 'monthly' => 230.40],
            's2.4xlarge.2' => ['hourly' => 0.64, 'monthly' => 460.80],
        ];

        $baseCost = $estimatedCosts[$instanceType] ?? ['hourly' => 0.10, 'monthly' => 72.00];
        
        // Add storage costs
        $storageCost = ($config['disk_size_gb'] ?? 40) * 0.03; // $0.03 per GB per month
        
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

    private function getAuthToken(array $credentials): string
    {
        $iamUrl = 'https://iam.{region}.myhuaweicloud.com/v3/auth/tokens';
        $iamUrl = str_replace('{region}', $credentials['region'] ?? 'eu-west-0', $iamUrl);
        
        $client = new Client();
        
        $payload = [
            'auth' => [
                'identity' => [
                    'methods' => ['password'],
                    'password' => [
                        'user' => [
                            'name' => $credentials['username'],
                            'password' => $credentials['password'],
                            'domain' => [
                                'name' => $credentials['domain_name'] ?? 'HWCloud',
                            ],
                        ],
                    ],
                ],
                'scope' => [
                    'project' => [
                        'name' => $credentials['project_name'] ?? 'eu-west-0',
                    ],
                ],
            ],
        ];

        $response = $client->post($iamUrl, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $payload,
        ]);

        return $response->getHeaderLine('X-Subject-Token');
    }

    private function ensureAuthenticated(): void
    {
        if (empty($this->token)) {
            $this->token = $this->getAuthToken($this->credentials);
        }
    }

    private function formatHuaweiMetrics($data): array
    {
        $formatted = [];
        foreach ($data['metrics'] ?? [] as $metric) {
            $formatted[] = [
                'metric_name' => $metric['metric_name'],
                'unit' => $metric['unit'],
                'datapoints' => $metric['datapoints'] ?? [],
            ];
        }
        return $formatted;
    }

    private function getDefaultImage(): string
    {
        // Return a default Ubuntu image ID for Huawei Cloud
        return 'd5b51d12-3342-4e5f-a2a4-c5fda47539d1';
    }

    private function getDefaultSubnet(): string
    {
        // Return a default subnet ID
        return '6cc8a3b6-4c3d-4c9e-8b5a-5b5e5b5e5b5e';
    }
}
