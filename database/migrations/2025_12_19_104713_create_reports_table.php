<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->morphs('reportable');
            $table->enum('reason', ['spam', 'harassment', 'hate_speech', 'violence', 'nudity', 'other']);
            $table->text('description')->nullable();
            $table->boolean('auto_detected')->default(false);
            $table->integer('spam_score')->nullable();
            $table->json('detection_reasons')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'resolved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('action_taken')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('status');
            $table->index('created_at');
            $table->index(['auto_detected', 'spam_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
