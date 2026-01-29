<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ab_test_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('variant');
            $table->string('event_type'); // view, click, conversion, etc.
            $table->json('event_data')->nullable();
            $table->timestamps();

            $table->index(['ab_test_id', 'variant', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_test_events');
    }
};