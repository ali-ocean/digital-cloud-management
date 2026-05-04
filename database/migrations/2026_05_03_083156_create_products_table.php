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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('github_repo_url');
            $table->string('github_branch')->default('main');
            $table->json('repository_config'); // Repo configuration
            $table->enum('deployment_type', ['host', 'docker']);
            $table->json('docker_config')->nullable(); // Docker configuration
            $table->foreignId('stack_id')->constrained()->onDelete('cascade');
            $table->string('deployment_path'); // Path on VM
            $table->json('environment_variables')->nullable();
            $table->json('setup_commands'); // Custom setup commands
            $table->enum('status', ['active', 'inactive', 'deploying', 'failed'])->default('inactive');
            $table->timestamp('last_deployed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
