<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing mentions table if exists
        Schema::dropIfExists('mentions');
        
        // Create new mentions table with correct polymorphic structure
        Schema::create('mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('mentionable'); // This creates mentionable_id and mentionable_type
            $table->timestamps();
            
            $table->unique(['user_id', 'mentionable_id', 'mentionable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentions');
    }
};