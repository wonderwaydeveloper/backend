<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('is_draft');
            $table->boolean('is_hidden')->default(false)->after('is_flagged');
            $table->boolean('is_deleted')->default(false)->after('is_hidden');
            $table->timestamp('flagged_at')->nullable()->after('is_deleted');
            $table->index(['is_flagged', 'is_hidden', 'is_deleted']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('remember_token');
            $table->boolean('is_suspended')->default(false)->after('is_flagged');
            $table->boolean('is_banned')->default(false)->after('is_suspended');
            $table->timestamp('suspended_until')->nullable()->after('is_suspended');
            $table->timestamp('banned_at')->nullable()->after('is_banned');
            $table->index(['is_flagged', 'is_suspended', 'is_banned']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->string('action_taken')->nullable()->after('reviewed_at');
            $table->text('admin_notes')->nullable()->after('action_taken');
            $table->index(['status', 'reportable_type']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['is_flagged', 'is_hidden', 'is_deleted']);
            $table->dropColumn(['is_flagged', 'is_hidden', 'is_deleted', 'flagged_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_flagged', 'is_suspended', 'is_banned']);
            $table->dropColumn(['is_flagged', 'is_suspended', 'is_banned', 'suspended_until', 'banned_at']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['status', 'reportable_type']);
            $table->dropColumn(['reviewed_by', 'reviewed_at', 'action_taken', 'admin_notes']);
        });
    }
};
