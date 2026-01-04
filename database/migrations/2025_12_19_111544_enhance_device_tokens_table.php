<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->string('browser')->nullable()->after('device_name');
            $table->string('os')->nullable()->after('browser');
            $table->string('push_token')->nullable()->after('os');
            $table->string('ip_address')->nullable()->after('push_token');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('fingerprint')->unique()->after('user_agent');
            $table->boolean('is_trusted')->default(false)->after('fingerprint');
            
            $table->index('fingerprint');
            $table->index(['user_id', 'is_trusted']);
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropIndex(['fingerprint']);
            $table->dropIndex(['user_id', 'is_trusted']);
            $table->dropColumn([
                'browser', 'os', 'push_token', 'ip_address', 
                'user_agent', 'fingerprint', 'is_trusted'
            ]);
        });
    }
};