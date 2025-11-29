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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->string('type')->default('post'); // post, reply, quote
            $table->foreignId('parent_id')->nullable()->constrained('posts')->onDelete('cascade');
            $table->foreignId('original_post_id')->nullable()->constrained('posts')->onDelete('cascade');
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_edited')->default(false);
            $table->integer('like_count')->default(0);
            $table->integer('reply_count')->default(0);
            $table->integer('repost_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->fullText(['content']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
