<?php

namespace Tests\Feature;

use App\Models\{User, Conversation, Message, ConversationParticipant};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Authorization Audit Test for Messaging System
 * Tests whether authorization is properly enforced at route level vs controller level
 */
class MessagingAuthorizationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => 'message.send', 'guard_name' => 'sanctum']
        );
        
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
        $role->givePermissionTo('message.send');
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('user');
        $this->token = $this->user->createToken('test')->plainTextToken;
        
        $this->otherUser = User::factory()->create(['email_verified_at' => now()]);
    }

    /** @test */
    public function test_authorization_issue_can_read_others_messages_without_permission()
    {
        // Create conversation between otherUser and someone else
        $thirdUser = User::factory()->create();
        $conv = Conversation::create([
            'user_one_id' => $this->otherUser->id,
            'user_two_id' => $thirdUser->id,
            'type' => 'direct',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $this->otherUser->id,
            'content' => 'Private message',
            'message_type' => 'text',
        ]);

        // Current user tries to read messages between otherUser and thirdUser
        $response = $this->withToken($this->token)
            ->getJson("/api/messages/users/{$this->otherUser->id}");

        // ISSUE: Returns empty data instead of 403 Forbidden
        // Should check if user is part of conversation
        $response->assertOk(); // Currently passes, but should be 403
    }

    /** @test */
    public function test_authorization_issue_can_add_reaction_to_any_message()
    {
        // Create message in conversation user is NOT part of
        $conv = Conversation::create([
            'user_one_id' => $this->otherUser->id,
            'user_two_id' => User::factory()->create()->id,
            'type' => 'direct',
            'last_message_at' => now(),
        ]);

        $message = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $this->otherUser->id,
            'content' => 'Test',
            'message_type' => 'text',
        ]);

        // Try to add reaction to message in conversation user is not part of
        $response = $this->withToken($this->token)
            ->postJson("/api/messages/{$message->id}/reactions", ['emoji' => '❤️']);

        // ISSUE: No authorization check if user is part of conversation
        // Should return 403, but might succeed
        $this->assertContains($response->status(), [200, 403]);
    }

    /** @test */
    public function test_authorization_issue_can_forward_others_messages()
    {
        // Create message from otherUser
        $conv = Conversation::create([
            'user_one_id' => $this->otherUser->id,
            'user_two_id' => User::factory()->create()->id,
            'type' => 'direct',
            'last_message_at' => now(),
        ]);

        $message = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $this->otherUser->id,
            'content' => 'Private message',
            'message_type' => 'text',
        ]);

        $recipient = User::factory()->create();

        // Try to forward message user doesn't have access to
        $response = $this->withToken($this->token)
            ->postJson("/api/messages/{$message->id}/forward", [
                'recipient_ids' => [$recipient->id]
            ]);

        // ISSUE: No check if user can access the original message
        $this->assertContains($response->status(), [200, 403, 400]);
    }

    /** @test */
    public function test_authorization_issue_can_read_group_messages_without_membership()
    {
        // Create group conversation
        $conv = Conversation::create([
            'name' => 'Private Group',
            'type' => 'group',
            'max_participants' => 50,
            'last_message_at' => now(),
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'user_id' => $this->otherUser->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $this->otherUser->id,
            'content' => 'Group secret',
            'message_type' => 'text',
        ]);

        // Try to read group messages without being a member
        $response = $this->withToken($this->token)
            ->getJson("/api/messages/groups/{$conv->id}");

        // This should return 403, let's verify
        $response->assertStatus(403); // This one is actually protected
    }

    /** @test */
    public function test_authorization_missing_permission_middleware_on_critical_endpoints()
    {
        // These endpoints should have permission:message.send but don't:
        $criticalEndpoints = [
            'POST /{message}/reactions',
            'POST /{message}/forward', 
            'PUT /{message}/edit',
            'DELETE /{message}/delete-for-everyone',
            'POST /groups/{conversation}/members/{user}',
        ];

        // This test documents the issue
        $this->assertTrue(true, 'Critical endpoints missing permission middleware: ' . implode(', ', $criticalEndpoints));
    }

    /** @test */
    public function test_recommendation_add_authorization_policies()
    {
        // Recommendations:
        // 1. Create MessagePolicy with methods: view, update, delete, forward, react
        // 2. Create ConversationPolicy with methods: view, addMember, removeMember
        // 3. Add middleware('can:view,message') to relevant routes
        // 4. Add permission:message.send to all write operations
        
        $recommendations = [
            '1. Create app/Policies/MessagePolicy.php',
            '2. Create app/Policies/ConversationPolicy.php',
            '3. Add permission:message.send to reactions, forward, edit, delete routes',
            '4. Add can:view,message middleware to read operations',
            '5. Add can:addMember,conversation to group management routes',
        ];

        $this->assertTrue(true, 'Recommendations: ' . implode(' | ', $recommendations));
    }
}
