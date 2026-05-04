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
        Schema::create('disaster_recoveries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('source_vm_id')->constrained('virtual_machines')->onDelete('cascade');
            $table->foreignId('dr_vm_id')->nullable()->constrained('virtual_machines')->onDelete('cascade');
            $table->foreignId('cloud_provider_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['inactive', 'active', 'failed', 'rollback'])->default('inactive');
            $table->enum('deployment_type', ['host', 'docker']);
            $table->json('dr_config'); // DR configuration
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('rollback_at')->nullable();
            $table->decimal('total_hours_running', 8, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->text('last_backup_used')->nullable();
            $table->json('dns_records')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disaster_recoveries');
    }
};
