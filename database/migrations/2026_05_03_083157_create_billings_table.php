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
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('virtual_machine_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('cloud_provider_id')->constrained()->onDelete('cascade');
            $table->string('billing_period'); // monthly, yearly
            $table->date('billing_date');
            $table->decimal('total_cost', 12, 2);
            $table->decimal('compute_cost', 10, 2)->default(0);
            $table->decimal('storage_cost', 10, 2)->default(0);
            $table->decimal('network_cost', 10, 2)->default(0);
            $table->decimal('other_cost', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->json('cost_breakdown')->nullable(); // Detailed breakdown
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
