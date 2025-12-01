<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateMessageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_conversation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = $user1->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/messages/conversations', [
            'user_ids' => [$user2->id],
            'type' => 'direct',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'direct');

        $this->assertDatabaseHas('conversations', [
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);

        $this->assertDatabaseHas('conversation_user', [
            'user_id' => $user1->id,
        ]);

        $this->assertDatabaseHas('conversation_user', [
            'user_id' => $user2->id,
        ]);
    }

    /** @test */
    public function underage_user_cannot_access_messaging()
    {
        $underageUser = User::factory()->create([
            'birth_date' => now()->subYears(16)->format('Y-m-d'),
            'is_underage' => true,
        ]);
        
        $token = $underageUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/messages/conversations');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Access denied due to parental controls']);
    }

    /** @test */
    public function user_can_send_message_in_conversation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        
        $conversation->users()->attach([$user1->id, $user2->id]);
        
        $token = $user1->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/messages/conversations/{$conversation->id}/messages", [
            'content' => 'Hello there!',
            'type' => 'text',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.content', 'Hello there!')
            ->assertJsonPath('data.user.id', $user1->id);

        $this->assertDatabaseHas('private_messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $user1->id,
            'content' => 'Hello there!',
        ]);
    }

    /** @test */
    public function non_participant_cannot_send_message()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create(); // Not in conversation
        
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->users()->attach([$user1->id, $user2->id]);
        
        $token = $user3->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/messages/conversations/{$conversation->id}/messages", [
            'content' => 'Unauthorized message',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_view_conversation_messages()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        
        $conversation->users()->attach([$user1->id, $user2->id]);
        
        $conversation->messages()->create([
            'user_id' => $user1->id,
            'content' => 'Message 1',
            'type' => 'text',
        ]);
        
        $conversation->messages()->create([
            'user_id' => $user2->id,
            'content' => 'Message 2',
            'type' => 'text',
        ]);
        
        $token = $user1->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/messages/conversations/{$conversation->id}/messages");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data');
    }

    /** @test */
    public function user_can_mark_messages_as_seen()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        
        $conversation->users()->attach([$user1->id, $user2->id]);
        
        $message = $conversation->messages()->create([
            'user_id' => $user2->id,
            'content' => 'Unseen message',
            'type' => 'text',
        ]);
        
        $token = $user1->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/messages/conversations/{$conversation->id}/mark-seen");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Messages marked as seen']);

        $this->assertNotNull($message->fresh()->seen_at);
    }

    /** @test */
    public function user_can_delete_their_message()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->users()->attach($user->id);
        
        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'content' => 'Message to delete',
            'type' => 'text',
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/messages/messages/{$message->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Message deleted successfully']);

        $this->assertNotNull($message->fresh()->deleted_at);
    }
}