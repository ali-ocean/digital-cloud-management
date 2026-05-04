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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('virtual_machine_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('backup_type'); // mongodb, files, database
            $table->string('storage_provider'); // gcp, digitalocean, huawei
            $table->string('bucket_name');
            $table->string('backup_path');
            $table->decimal('size_gb', 10, 2);
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->json('backup_config'); // Backup configuration
            $table->json('schedule_config'); // Scheduling configuration
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
