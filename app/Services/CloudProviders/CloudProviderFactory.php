<?php

namespace App\Services\CloudProviders;

use Exception;

class CloudProviderFactory
{
    public static function create(string $provider, array $credentials): CloudProviderInterface
    {
        switch (strtolower($provider)) {
            case 'gcp':
            case 'google':
                return new GCPProvider($credentials);
            
            case 'digitalocean':
            case 'do':
                return new DigitalOceanProvider($credentials);
            
            case 'huawei':
            case 'hw':
                return new HuaweiProvider($credentials);
            
            default:
                throw new Exception("Unsupported cloud provider: {$provider}");
        }
    }

    public static function getSupportedProviders(): array
    {
        return [
            'gcp' => 'Google Cloud Platform',
            'digitalocean' => 'DigitalOcean',
            'huawei' => 'Huawei Cloud',
        ];
    }

    public static function validateCredentials(string $provider, array $credentials): array
    {
        $requiredFields = [
            'gcp' => ['project_id', 'service_account_key'],
            'digitalocean' => ['api_key'],
            'huawei' => ['username', 'password', 'project_name', 'region'],
        ];

        $provider = strtolower($provider);
        if (!isset($requiredFields[$provider])) {
            return ['valid' => false, 'errors' => ["Unsupported provider: {$provider}"]];
        }

        $errors = [];
        foreach ($requiredFields[$provider] as $field) {
            if (!isset($credentials[$field]) || empty($credentials[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
