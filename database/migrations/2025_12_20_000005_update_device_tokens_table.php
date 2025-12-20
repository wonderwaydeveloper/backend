<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->renameColumn('platform', 'device_type');
        });

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->string('device_name')->nullable()->after('device_type');
            $table->boolean('active')->default(true)->after('device_name');
            $table->timestamp('last_used_at')->nullable()->after('active');
            
            $table->index(['user_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'active']);
            $table->dropColumn(['device_name', 'active', 'last_used_at']);
        });

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->renameColumn('device_type', 'platform');
        });
    }
};