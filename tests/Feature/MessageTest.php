<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Conversation;
use App\Models\PrivateMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_conversation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Sanctum::actingAs($user1);

        $response = $this->postJson('/api/messages/conversations', [
            'user_ids' => [$user2->id],
            'type' => 'direct',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'type', 'participants'],
            ]);

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
    public function user_can_send_message_to_conversation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $conversation->users()->attach([$user1->id, $user2->id]);

        Sanctum::actingAs($user1);

        $response = $this->postJson("/api/messages/conversations/{$conversation->id}/messages", [
            'content' => 'Hello, how are you?',
            'type' => 'text',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'content' => 'Hello, how are you?',
                    'user' => ['id' => $user1->id],
                ],
            ]);

        $this->assertDatabaseHas('private_messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $user1->id,
            'content' => 'Hello, how are you?',
        ]);
    }

    /** @test */
    public function user_can_view_conversations()
    {
        $user = User::factory()->create();
        $conversations = Conversation::factory()->count(3)->create();

        foreach ($conversations as $conversation) {
            $conversation->users()->attach($user->id);
        }

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/messages/conversations');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_can_view_messages_in_conversation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $conversation->users()->attach([$user1->id, $user2->id]);

        PrivateMessage::factory()->count(5)->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user1->id,
        ]);

        Sanctum::actingAs($user1);

        $response = $this->getJson("/api/messages/conversations/{$conversation->id}/messages");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function user_cannot_view_messages_in_conversation_they_are_not_part_of()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $intruder = User::factory()->create();
        
        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $conversation->users()->attach([$user1->id, $user2->id]);

        Sanctum::actingAs($intruder);

        $response = $this->getJson("/api/messages/conversations/{$conversation->id}/messages");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_mark_messages_as_seen()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::create([
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
        $conversation->users()->attach([$user1->id, $user2->id]);

        $message = PrivateMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user2->id,
            'seen_at' => null,
        ]);

        Sanctum::actingAs($user1);

        $response = $this->postJson("/api/messages/conversations/{$conversation->id}/mark-seen");

        $response->assertStatus(200)
            ->assertJson(['data' => ['marked' => true]]);

        $this->assertNotNull($message->fresh()->seen_at);
    }

    /** @test */
    public function user_can_delete_own_message()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->users()->attach($user->id);

        $message = PrivateMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/messages/messages/{$message->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['deleted' => true]]);

        $this->assertNotNull($message->fresh()->deleted_at);
    }

    /** @test */
    public function user_cannot_delete_other_users_messages()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->users()->attach([$user1->id, $user2->id]);

        $message = PrivateMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user2->id,
        ]);

        Sanctum::actingAs($user1);

        $response = $this->deleteJson("/api/messages/messages/{$message->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_any_message()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->users()->attach($user->id);

        $message = PrivateMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/messages/messages/{$message->id}");

        $response->assertStatus(200);
        $this->assertNotNull($message->fresh()->deleted_at);
    }

    /** @test */
    public function user_can_create_group_conversation()
    {
        $user = User::factory()->create();
        $users = User::factory()->count(3)->create();
        Sanctum::actingAs($user);

        $userIds = $users->pluck('id')->toArray();

        $response = $this->postJson('/api/messages/conversations', [
            'user_ids' => $userIds,
            'type' => 'group',
            'title' => 'Group Chat',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'type' => 'group',
                    'title' => 'Group Chat',
                ],
            ]);

        $conversation = Conversation::latest()->first();
        $this->assertEquals(count($userIds) + 1, $conversation->users()->count()); // +1 for creator
    }

    /** @test */
    public function user_can_add_participant_to_group_conversation()
    {
        $admin = User::factory()->create();
        $newUser = User::factory()->create();
        $conversation = Conversation::create([
            'type' => 'group',
            'created_by' => $admin->id,
            'title' => 'Test Group',
        ]);
        $conversation->users()->attach($admin->id, ['role' => 'admin']);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/messages/conversations/{$conversation->id}/add-participant", [
            'user_id' => $newUser->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['added' => true]]);

        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $conversation->id,
            'user_id' => $newUser->id,
        ]);
    }

    /** @test */
    public function user_can_leave_group_conversation()
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'type' => 'group',
            'created_by' => $user->id,
            'title' => 'Test Group',
        ]);
        $conversation->users()->attach($user->id);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/messages/conversations/{$conversation->id}/leave");

        $response->assertStatus(200)
            ->assertJson(['data' => ['left' => true]]);

        $this->assertDatabaseHas('conversation_user', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'left_at' => now(),
        ]);
    }

    /** @test */
    public function underage_user_cannot_access_private_messaging()
    {
        $child = User::factory()->create(['is_underage' => true]);
        Sanctum::actingAs($child);

        $response = $this->getJson('/api/messages/conversations');

        $response->assertStatus(403);
    }
}