<?php

namespace App\Services\CloudProviders;

interface CloudProviderInterface
{
    public function authenticate(array $credentials): bool;
    public function listInstances(): array;
    public function getInstance(string $instanceId): array;
    public function createInstance(array $config): array;
    public function startInstance(string $instanceId): bool;
    public function stopInstance(string $instanceId): bool;
    public function deleteInstance(string $instanceId): bool;
    public function getInstanceMetrics(string $instanceId): array;
    public function getBillingInfo(string $projectId): array;
    public function listRegions(): array;
    public function listInstanceTypes(): array;
    public function getCostEstimate(array $config): array;
}
