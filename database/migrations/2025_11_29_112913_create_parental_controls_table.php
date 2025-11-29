<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parental_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('child_id')->constrained('users')->onDelete('cascade');
            $table->json('restrictions')->nullable(); // {max_daily_usage: 120, content_filter: true, etc.}
            $table->json('allowed_features')->nullable();
            $table->time('daily_limit_start')->nullable();
            $table->time('daily_limit_end')->nullable();
            $table->integer('max_daily_usage')->default(120); // minutes
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['parent_id', 'child_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parental_controls');
    }
};
