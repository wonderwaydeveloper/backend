<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add performance indexes
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['created_at', 'user_id'], 'posts_timeline_index');
            $table->index(['user_id', 'created_at'], 'posts_user_timeline_index');
        });

        Schema::table('follows', function (Blueprint $table) {
            $table->index(['follower_id', 'created_at'], 'follows_timeline_index');
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->index(['likeable_id', 'likeable_type', 'created_at'], 'likes_polymorphic_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['post_id', 'created_at'], 'comments_post_index');
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_timeline_index');
            $table->dropIndex('posts_user_timeline_index');
        });

        Schema::table('follows', function (Blueprint $table) {
            $table->dropIndex('follows_timeline_index');
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->dropIndex('likes_polymorphic_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_index');
        });
    }
};