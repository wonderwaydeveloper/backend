<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('slug')->unique();
            $table->string('avatar')->nullable();
            $table->string('banner')->nullable();
            $table->enum('privacy', ['public', 'private', 'restricted'])->default('public');
            $table->json('rules')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->integer('member_count')->default(0);
            $table->integer('post_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            
            $table->index(['privacy', 'created_at']);
            $table->index('member_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};