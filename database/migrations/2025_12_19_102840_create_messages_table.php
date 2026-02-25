<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->enum('message_type', ['text', 'voice', 'video', 'gif', 'link'])->default('text');
            $table->text('content')->nullable();
            $table->string('gif_url')->nullable();
            $table->integer('voice_duration')->nullable()->comment('Duration in seconds');
            $table->foreignId('forwarded_from_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('sender_id');
            $table->index('read_at');
            $table->index(['conversation_id', 'sender_id', 'created_at'], 'messages_conversation_sender_created_idx');
            $table->index(['sender_id', 'read_at', 'deleted_at'], 'idx_unread_messages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
