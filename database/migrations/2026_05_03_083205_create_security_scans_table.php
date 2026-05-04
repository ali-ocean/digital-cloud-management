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
        Schema::create('security_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_machine_id')->constrained()->onDelete('cascade');
            $table->string('scan_type'); // vulnerability, pentest, compliance
            $table->string('scanner_tool'); // nmap, nikto, openvas, etc.
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->json('scan_config'); // Scan configuration
            $table->json('results')->nullable(); // Scan results
            $table->integer('critical_vulnerabilities')->default(0);
            $table->integer('high_vulnerabilities')->default(0);
            $table->integer('medium_vulnerabilities')->default(0);
            $table->integer('low_vulnerabilities')->default(0);
            $table->boolean('nca_compliant')->default(false);
            $table->text('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_scans');
    }
};
