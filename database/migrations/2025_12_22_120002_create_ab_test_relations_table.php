<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ab_test_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('variant'); // A or B
            $table->timestamp('assigned_at');

            $table->unique(['ab_test_id', 'user_id']);
            $table->index('variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_test_participants');
    }
};