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
        Schema::create('cicd_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('virtual_machine_id')->constrained()->onDelete('cascade');
            $table->string('github_webhook_url')->nullable();
            $table->string('github_webhook_secret')->nullable();
            $table->json('pipeline_config'); // Pipeline stages and configuration
            $table->json('deployment_script'); // Deployment bash script
            $table->json('rollback_script'); // Rollback script
            $table->enum('status', ['active', 'inactive', 'running', 'failed'])->default('inactive');
            $table->enum('trigger_type', ['webhook', 'manual', 'scheduled']);
            $table->json('trigger_config')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cicd_pipelines');
    }
};
