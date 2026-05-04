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
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('virtual_machine_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->string('type'); // A, CNAME, TXT, MX, etc.
            $table->string('value');
            $table->integer('ttl')->default(3600);
            $table->boolean('is_active')->default(true);
            $table->string('cloudflare_zone_id')->nullable();
            $table->string('cloudflare_record_id')->nullable();
            $table->json('cloudflare_config')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }
};
