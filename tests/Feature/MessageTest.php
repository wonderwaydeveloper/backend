<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Jobs\ProcessMessageJob;
use App\Models\{Conversation, Message, User};
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Event, Queue};
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected $messageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageService = app(MessageService::class);
    }

    public function test_send_message_creates_conversation_and_message()
    {
        Event::fake();
        Queue::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $message = $this->messageService->sendMessage($sender, $recipient, [
            'content' => 'Hello World'
        ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'content' => 'Hello World'
        ]);

        $this->assertDatabaseHas('conversations', [
            'user_one_id' => $sender->id,
            'user_two_id' => $recipient->id
        ]);

        Event::assertDispatched(MessageSent::class);
        Queue::assertPushed(ProcessMessageJob::class);
    }

    public function test_cannot_send_message_to_self()
    {
        $user = User::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot send message to yourself');

        $this->messageService->sendMessage($user, $user, ['content' => 'Test']);
    }

    public function test_cannot_send_message_to_blocked_user()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $sender->blockedUsers()->attach($recipient->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot send message to blocked user');

        $this->messageService->sendMessage($sender, $recipient, ['content' => 'Test']);
    }

    public function test_xss_protection_strips_tags()
    {
        Event::fake();
        Queue::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $message = $this->messageService->sendMessage($sender, $recipient, [
            'content' => '<script>alert("xss")</script>Hello'
        ]);

        $this->assertEquals('alert("xss")Hello', $message->content);
        $this->assertStringNotContainsString('<script>', $message->content);
        $this->assertStringNotContainsString('</script>', $message->content);
    }

    public function test_get_conversations_returns_user_conversations()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $conversation = Conversation::create([
            'user_one_id' => $user->id,
            'user_two_id' => $other->id,
            'last_message_at' => now()
        ]);

        $conversations = $this->messageService->getConversations($user);

        $this->assertCount(1, $conversations);
    }

    public function test_mark_as_read_updates_message()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $conversation = Conversation::create([
            'user_one_id' => $sender->id,
            'user_two_id' => $recipient->id,
            'last_message_at' => now()
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'content' => 'Test'
        ]);

        $this->messageService->markAsRead($message, $recipient);

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_unread_count_returns_correct_count()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $conversation = Conversation::create([
            'user_one_id' => $user->id,
            'user_two_id' => $other->id,
            'last_message_at' => now()
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
            'content' => 'Test 1'
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
            'content' => 'Test 2'
        ]);

        $count = $this->messageService->getUnreadCount($user);

        $this->assertEquals(2, $count);
    }
}
