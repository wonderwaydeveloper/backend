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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->nullable();
            $table->string('fingerprint')->index(); // Added missing fingerprint field
            $table->string('user_agent')->nullable();
            $table->string('ip_address');
            $table->boolean('is_trusted')->default(false); // Fixed naming consistency
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'fingerprint']); // Prevent duplicate devices
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
