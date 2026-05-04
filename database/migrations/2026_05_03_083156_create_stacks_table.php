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
        Schema::create('stacks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // MongoDB 7.0, Laravel, Node.js, React, Next.js, Go
            $table->string('type'); // database, backend, frontend, fullstack
            $table->string('version');
            $table->text('description')->nullable();
            $table->json('configuration'); // Default configuration
            $table->json('requirements'); // System requirements
            $table->json('installation_commands'); // Commands to install
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stacks');
    }
};
