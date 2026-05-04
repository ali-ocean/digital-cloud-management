<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cloud_provider_id',
        'cloud_project_id',
        'environment',
        'owner',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    public function cloudProvider(): BelongsTo
    {
        return $this->belongsTo(CloudProvider::class);
    }

    public function virtualMachines(): HasMany
    {
        return $this->hasMany(VirtualMachine::class);
    }

    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function disasterRecoveries(): HasMany
    {
        return $this->hasMany(DisasterRecovery::class);
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

    public function getMonthlyCost(): float
    {
        return $this->billings()
            ->where('billing_period', 'monthly')
            ->sum('total_cost');
    }

    public function getActiveVmsCount(): int
    {
        return $this->virtualMachines()
            ->where('status', 'running')
            ->count();
    }
}
