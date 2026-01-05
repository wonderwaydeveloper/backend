<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentalControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_child()
    {
        $parent = User::factory()->create(['date_of_birth' => '1980-01-01']);
        $child = User::factory()->create([
            'is_child' => true,
            'date_of_birth' => '2010-01-01'
        ]);

        $response = $this->actingAs($parent)->postJson('/api/parental/link-child', [
            'child_email' => $child->email,
        ]);

        $response->assertStatus(201)
                ->assertJson(['message' => 'Link request sent successfully']);

        $this->assertDatabaseHas('parental_links', [
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'status' => 'pending'
        ]);
    }

    public function test_approve_link()
    {
        $parent = User::factory()->create(['date_of_birth' => '1980-01-01']);
        $child = User::factory()->create([
            'is_child' => true,
            'date_of_birth' => '2010-01-01'
        ]);

        // Create pending link
        \DB::table('parental_links')->insert([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->actingAs($child)->postJson('/api/parental/approve-link', [
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Parental link approved']);

        $this->assertDatabaseHas('parental_links', [
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'status' => 'approved'
        ]);
    }

    public function test_get_child_activity()
    {
        $parent = User::factory()->create(['date_of_birth' => '1980-01-01']);
        $child = User::factory()->create([
            'is_child' => true,
            'date_of_birth' => '2010-01-01'
        ]);

        // Create approved link
        \DB::table('parental_links')->insert([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->actingAs($parent)->getJson("/api/parental/child-activity/{$child->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'child',
                    'activity' => [
                        'posts_count',
                        'followers_count',
                        'following_count',
                        'last_active'
                    ]
                ]);
    }

    public function test_child_cannot_link_parent()
    {
        $child = User::factory()->create([
            'is_child' => true,
            'date_of_birth' => '2010-01-01'
        ]);
        $parent = User::factory()->create(['date_of_birth' => '1980-01-01']);

        $response = $this->actingAs($child)->postJson('/api/parental/link-child', [
            'child_email' => $parent->email,
        ]);

        $response->assertStatus(403);
    }

    public function test_age_verification_during_registration()
    {
        // First create a valid session
        $sessionId = \Str::uuid();
        \Cache::put("registration:{$sessionId}", [
            'contact' => 'young@example.com',
            'contact_type' => 'email',
            'code' => '123456',
            'step' => 2,
            'verified' => true
        ], now()->addMinutes(15));

        $response = $this->postJson('/api/auth/register/step3', [
            'session_id' => $sessionId,
            'name' => 'Young User',
            'username' => 'younguser',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'date_of_birth' => '2015-01-01' // Under 13
        ]);

        // Should create user but mark as child
        $response->assertStatus(201);
        
        $user = User::where('username', 'younguser')->first();
        $this->assertTrue($user->is_child);
    }
}
