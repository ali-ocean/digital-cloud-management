<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'credentials',
        'region',
        'is_active',
        'description',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function virtualMachines(): HasMany
    {
        return $this->hasMany(VirtualMachine::class);
    }

    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class);
    }

    public function disasterRecoveries(): HasMany
    {
        return $this->hasMany(DisasterRecovery::class);
    }

    public function getProviderService()
    {
        return \App\Services\CloudProviders\CloudProviderFactory::create(
            $this->type,
            $this->credentials
        );
    }

    public function testConnection(): bool
    {
        try {
            $service = $this->getProviderService();
            return $service->authenticate($this->credentials);
        } catch (\Exception $e) {
            return false;
        }
    }
}
