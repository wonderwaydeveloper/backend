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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('content');
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Performance indexes
            $table->index(['post_id', 'created_at'], 'comments_post_index');
            $table->index('user_id', 'comments_user_index');
            $table->index('parent_id', 'comments_parent_index');
            $table->index('is_pinned', 'comments_pinned_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
