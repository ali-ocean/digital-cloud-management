<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('virtual_machines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('cloud_provider_id')->constrained()->onDelete('cascade');
            $table->string('cloud_vm_id'); // VM instance ID in cloud provider
            $table->string('instance_type'); // e.g., e2-medium, n1-standard-1
            $table->string('status'); // running, stopped, terminated, etc.
            $table->string('public_ip')->nullable();
            $table->string('private_ip')->nullable();
            $table->integer('cpu_cores');
            $table->integer('ram_gb');
            $table->integer('disk_gb');
            $table->string('os_type'); // ubuntu, centos, etc.
            $table->string('os_version');
            $table->string('region');
            $table->string('zone');
            $table->json('tags')->nullable();
            $table->decimal('hourly_cost', 8, 4)->default(0);
            $table->decimal('monthly_cost', 10, 2)->default(0);
            $table->boolean('is_monitored')->default(false);
            $table->boolean('is_dr_vm')->default(false);
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_machines');
    }
};
