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
        Schema::create('cloud_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // GCP, DigitalOcean, Huawei
            $table->string('type'); // gcp, digitalocean, huawei
            $table->json('credentials'); // API credentials encrypted
            $table->string('region')->default('us-central1');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloud_providers');
    }
};
