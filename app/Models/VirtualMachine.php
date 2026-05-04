<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualMachine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'project_id',
        'cloud_provider_id',
        'cloud_vm_id',
        'instance_type',
        'status',
        'public_ip',
        'private_ip',
        'cpu_cores',
        'ram_gb',
        'disk_gb',
        'os_type',
        'os_version',
        'region',
        'zone',
        'tags',
        'hourly_cost',
        'monthly_cost',
        'is_monitored',
        'is_dr_vm',
        'provisioned_at',
        'last_seen_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'hourly_cost' => 'decimal:4',
        'monthly_cost' => 'decimal:2',
        'is_monitored' => 'boolean',
        'is_dr_vm' => 'boolean',
        'provisioned_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function cloudProvider(): BelongsTo
    {
        return $this->belongsTo(CloudProvider::class);
    }

    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class);
    }

    public function monitoringMetrics(): HasMany
    {
        return $this->hasMany(MonitoringMetric::class);
    }

    public function securityScans(): HasMany
    {
        return $this->hasMany(SecurityScan::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(DnsRecord::class);
    }

    public function cicdPipelines(): HasMany
    {
        return $this->hasMany(CicdPipeline::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function getProviderService()
    {
        return $this->cloudProvider->getProviderService();
    }

    public function syncWithCloud(): bool
    {
        try {
            $service = $this->getProviderService();
            $cloudData = $service->getInstance($this->cloud_vm_id);
            
            $this->update([
                'status' => $cloudData['status'],
                'public_ip' => $cloudData['networkInterfaces'][0]['accessConfigs'][0]['natIP'] ?? null,
                'private_ip' => $cloudData['networkInterfaces'][0]['networkIP'] ?? null,
                'last_seen_at' => now(),
            ]);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLatestMetrics(): array
    {
        return $this->monitoringMetrics()
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get()
            ->groupBy('metric_type')
            ->map(function ($metrics) {
                return $metrics->first();
            })
            ->toArray();
    }

    public function getUtilizationStats(): array
    {
        $latestMetrics = $this->getLatestMetrics();
        
        return [
            'cpu_utilization' => $latestMetrics['cpu']['value'] ?? 0,
            'memory_utilization' => $latestMetrics['memory']['value'] ?? 0,
            'disk_utilization' => $latestMetrics['disk']['value'] ?? 0,
            'network_in' => $latestMetrics['network']['value'] ?? 0,
            'network_out' => $latestMetrics['network_out']['value'] ?? 0,
        ];
    }
}
