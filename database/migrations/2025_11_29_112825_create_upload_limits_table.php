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
        Schema::create('upload_limits', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // post, article, message, comment
            $table->integer('max_files')->default(5);
            $table->integer('max_file_size')->default(10240); // KB
            $table->json('allowed_mimes')->nullable(); // ['jpg', 'png', 'gif']
            $table->integer('max_total_size')->default(51200); // KB
            $table->boolean('is_video_allowed')->default(true);
            $table->integer('max_video_duration')->default(300); // seconds
            $table->integer('max_video_size')->default(51200); // KB
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_limits');
    }
};
