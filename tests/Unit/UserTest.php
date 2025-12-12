<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Follow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_be_created()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function user_has_hashed_password()
    {
        $user = User::factory()->create([
            'password' => 'secret123'
        ]);

        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotEquals('secret123', $user->password);
    }

    #[Test]
    public function user_can_have_posts()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Post::class, $user->posts->first());
        $this->assertEquals($post->id, $user->posts->first()->id);
    }

    #[Test]
    public function user_can_follow_other_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Follow::create([
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
            'approved_at' => now(),
        ]);

        $this->assertTrue($user1->isFollowing($user2));
        $this->assertTrue($user2->isFollowedBy($user1));
    }

    #[Test]
    public function user_can_check_if_private_account_can_be_followed()
    {
        $user1 = User::factory()->create(['is_private' => true]);
        $user2 = User::factory()->create();

        // User2 cannot follow private user1 without being followed back
        $this->assertFalse($user2->canFollow($user1));

        // Make user2 follow user1
        Follow::create([
            'follower_id' => $user2->id,
            'following_id' => $user1->id,
            'approved_at' => now(),
        ]);

        // Now user2 can follow user1
        $this->assertTrue($user2->canFollow($user1));
    }

    #[Test]
    public function user_age_is_calculated_correctly()
    {
        $birthDate = now()->subYears(25)->subMonths(6);
        $user = User::factory()->create([
            'birth_date' => $birthDate->format('Y-m-d')
        ]);

        $this->assertEquals(25, $user->age);
    }

    #[Test]
    public function user_marked_as_underage_if_under_18()
    {
        $birthDate = now()->subYears(16);
        $user = User::factory()->create([
            'birth_date' => $birthDate->format('Y-m-d')
        ]);

        $this->assertTrue($user->is_underage);
    }

    #[Test]
    public function user_can_enable_two_factor()
    {
        $user = User::factory()->create();
        $user->enableTwoFactor();

        $this->assertTrue($user->fresh()->two_factor_enabled);
        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    #[Test]
    public function user_can_disable_two_factor()
    {
        $user = User::factory()->create();
        $user->enableTwoFactor();
        $user->disableTwoFactor();

        $this->assertFalse($user->two_factor_enabled);
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
    }
}