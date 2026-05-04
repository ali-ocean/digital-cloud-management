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
        Schema::create('monitoring_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_machine_id')->constrained()->onDelete('cascade');
            $table->string('metric_type'); // cpu, memory, disk, network
            $table->string('metric_name'); // cpu_usage, memory_usage, disk_usage, network_in, network_out
            $table->decimal('value', 10, 4);
            $table->string('unit'); // percent, bytes, mbps, etc.
            $table->timestamp('recorded_at');
            $table->json('additional_data')->nullable();
            $table->timestamps();
            
            $table->index(['virtual_machine_id', 'metric_type', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_metrics');
    }
};
