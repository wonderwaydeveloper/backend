<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\UploadedFile;

class TestHelper
{
    /**
     * Create a user with specific role
     */
    public static function createUserWithRole(string $role = 'user'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    /**
     * Create authenticated user with token
     */
    public static function createAuthenticatedUser(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        
        return [
            'user' => $user,
            'token' => $token,
            'headers' => ['Authorization' => 'Bearer ' . $token]
        ];
    }

    /**
     * Create post with specific engagement metrics
     */
    public static function createPopularPost(int $likes = 100, int $comments = 50): Post
    {
        $post = Post::factory()->create([
            'likes_count' => $likes,
            'comments_count' => $comments
        ]);

        // Create actual likes and comments
        $users = User::factory()->count($likes)->create();
        foreach ($users as $user) {
            $post->likes()->create(['user_id' => $user->id]);
        }

        return $post;
    }

    /**
     * Create fake image file for testing
     */
    public static function createFakeImage(string $name = 'test.jpg', int $size = 1024): UploadedFile
    {
        return UploadedFile::fake()->image($name, 800, 600)->size($size);
    }

    /**
     * Create fake video file for testing
     */
    public static function createFakeVideo(string $name = 'test.mp4', int $size = 1024): UploadedFile
    {
        return UploadedFile::fake()->create($name, $size, 'video/mp4');
    }

    /**
     * Assert API response structure
     */
    public static function assertApiResponseStructure(array $response, array $expectedStructure): void
    {
        foreach ($expectedStructure as $key) {
            \PHPUnit\Framework\Assert::assertArrayHasKey($key, $response);
        }
    }

    /**
     * Create user network (followers/following)
     */
    public static function createUserNetwork(User $user, int $followers = 10, int $following = 5): void
    {
        // Create followers
        $followerUsers = User::factory()->count($followers)->create();
        foreach ($followerUsers as $follower) {
            $user->followers()->attach($follower->id);
        }

        // Create following
        $followingUsers = User::factory()->count($following)->create();
        foreach ($followingUsers as $followingUser) {
            $user->following()->attach($followingUser->id);
        }

        // Update counts
        $user->update([
            'followers_count' => $followers,
            'following_count' => $following
        ]);
    }

    /**
     * Simulate time passage for testing time-based features
     */
    public static function travelInTime(\DateTimeInterface $date): void
    {
        \Illuminate\Support\Facades\Date::setTestNow($date);
    }

    /**
     * Reset time to current
     */
    public static function resetTime(): void
    {
        \Illuminate\Support\Facades\Date::setTestNow();
    }

    /**
     * Create test data for performance testing
     */
    public static function createLargeDataset(): array
    {
        $users = User::factory()->count(1000)->create();
        $posts = Post::factory()->count(5000)->create();
        
        return [
            'users' => $users,
            'posts' => $posts
        ];
    }

    /**
     * Assert response time is within acceptable limits
     */
    public static function assertResponseTime(float $startTime, float $maxTime = 1.0): void
    {
        $executionTime = microtime(true) - $startTime;
        \PHPUnit\Framework\Assert::assertLessThan(
            $maxTime, 
            $executionTime, 
            "Response time {$executionTime}s exceeded maximum {$maxTime}s"
        );
    }

    /**
     * Create malicious input for security testing
     */
    public static function getMaliciousInputs(): array
    {
        return [
            'sql_injection' => "'; DROP TABLE users; --",
            'xss_script' => '<script>alert("XSS")</script>',
            'xss_img' => '<img src="x" onerror="alert(1)">',
            'path_traversal' => '../../etc/passwd',
            'null_byte' => "test\0.php",
            'long_string' => str_repeat('A', 10000),
            'unicode_attack' => '𝕏𝕊𝕊',
            'html_entities' => '&lt;script&gt;alert(1)&lt;/script&gt;'
        ];
    }

    /**
     * Assert security headers are present
     */
    public static function assertSecurityHeaders($response): void
    {
        $securityHeaders = [
            'X-Frame-Options',
            'X-Content-Type-Options', 
            'X-XSS-Protection',
            'Strict-Transport-Security'
        ];

        foreach ($securityHeaders as $header) {
            \PHPUnit\Framework\Assert::assertTrue(
                $response->headers->has($header),
                "Security header {$header} is missing"
            );
        }
    }
}