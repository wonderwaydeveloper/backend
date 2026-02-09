<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Twitter-standard profile fields
            $table->string('display_name')->after('name')->nullable();
            $table->unsignedBigInteger('pinned_tweet_id')->nullable()->after('bio');
            $table->string('profile_link_color', 7)->default('#1DA1F2')->after('cover');
            $table->string('profile_text_color', 7)->default('#14171A')->after('profile_link_color');
            
            // Enhanced verification system (replace simple boolean)
            $table->enum('verification_type', ['none', 'blue', 'gold', 'gray'])->default('none')->after('verified');
            $table->timestamp('verified_at')->nullable()->after('verification_type');
            
            // Privacy & Safety (enhanced settings)
            $table->enum('allow_dms_from', ['everyone', 'following', 'none'])->default('everyone')->after('is_private');
            $table->boolean('quality_filter')->default(true)->after('allow_dms_from');
            $table->boolean('allow_sensitive_media')->default(false)->after('quality_filter');
            
            // Activity tracking (replace posts_count with tweets_count)
            $table->unsignedInteger('listed_count')->default(0)->after('following_count');
            $table->unsignedInteger('favourites_count')->default(0)->after('listed_count');
            
            // Indexes
            $table->index('display_name');
            $table->index('verification_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'display_name', 'pinned_tweet_id',
                'profile_link_color', 'profile_text_color', 'verification_type',
                'verified_at', 'allow_dms_from',
                'quality_filter', 'allow_sensitive_media',
                'listed_count', 'favourites_count'
            ]);
        });
    }
};