<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_verification_token')->nullable()->after('email_verified_at');
            $table->string('refresh_token')->nullable()->after('remember_token');
            $table->timestamp('password_changed_at')->nullable()->after('password');
            
            $table->index('email_verification_token');
            $table->index('refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email_verification_token']);
            $table->dropIndex(['refresh_token']);
            $table->dropColumn(['email_verification_token', 'refresh_token', 'password_changed_at']);
        });
    }
};