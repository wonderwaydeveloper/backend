<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->string('image')->nullable();
            $table->string('video')->nullable();
            $table->string('gif_url')->nullable();
            $table->boolean('is_draft')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('flagged_at')->nullable();
            $table->boolean('is_thread')->default(false);
            $table->string('reply_settings')->default('everyone');
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('reposts_count')->default(0);
            $table->unsignedInteger('quotes_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            
            // Twitter/X Analytics Metrics
            $table->unsignedBigInteger('impression_count')->default(0);
            $table->unsignedInteger('url_link_clicks')->default(0);
            $table->unsignedInteger('user_profile_clicks')->default(0);
            $table->unsignedInteger('hashtag_clicks')->default(0);
            $table->unsignedInteger('video_views')->default(0);
            $table->unsignedInteger('video_25_percent')->default(0);
            $table->unsignedInteger('video_50_percent')->default(0);
            $table->unsignedInteger('video_75_percent')->default(0);
            $table->unsignedInteger('video_100_percent')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->foreignId('quoted_post_id')->nullable()->constrained('posts')->onDelete('cascade');
            $table->foreignId('thread_id')->nullable()->constrained('posts')->onDelete('cascade');
            $table->integer('thread_position')->nullable();
            $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_edited_at')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'published_at']);
            $table->index(['created_at', 'published_at']);
            $table->index(['likes_count', 'comments_count']);
            $table->index(['is_flagged', 'is_hidden', 'is_deleted']);
            $table->index('quoted_post_id');
            $table->index(['thread_id', 'thread_position']);
            $table->index(['community_id', 'created_at']);
            $table->index(['community_id', 'is_pinned']);
            // Performance indexes
            $table->index(['user_id', 'is_draft', 'published_at'], 'posts_timeline_idx');
            $table->index(['published_at', 'likes_count'], 'posts_trending_idx');
            $table->index(['created_at', 'user_id'], 'posts_timeline_index');
            $table->index(['user_id', 'created_at'], 'posts_user_timeline_index');
            // Analytics indexes
            $table->index(['impression_count', 'created_at']);
            $table->index(['engagement_rate', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
