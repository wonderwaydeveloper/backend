<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\SubscriptionLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubscriptionLimitService::class);
        
        // Create roles
        $roles = ['user', 'verified', 'premium', 'organization', 'moderator', 'admin'];
        foreach ($roles as $role) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'sanctum']);
        }
    }

    /** @test */
    public function test_user_role_gets_correct_limits()
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->assertEquals(4, $this->service->getMaxMediaPerPost($user));
        $this->assertEquals(5120, $this->service->getMaxFileSize($user));
        $this->assertEquals(100, $this->service->getPostsPerDayLimit($user));
        $this->assertEquals(140, $this->service->getMaxVideoLength($user));
        $this->assertEquals(0, $this->service->getScheduledPostsLimit($user));
        $this->assertEquals(60, $this->service->getRateLimit($user));
        $this->assertFalse($this->service->canUploadHD($user));
        $this->assertFalse($this->service->canCreateAdvertisements($user));
    }

    /** @test */
    public function test_verified_role_gets_correct_limits()
    {
        $user = User::factory()->create();
        $user->assignRole('verified');

        $this->assertEquals(4, $this->service->getMaxMediaPerPost($user));
        $this->assertEquals(10240, $this->service->getMaxFileSize($user));
        $this->assertEquals(200, $this->service->getPostsPerDayLimit($user));
        $this->assertFalse($this->service->canUploadHD($user));
    }

    /** @test */
    public function test_premium_role_gets_correct_limits()
    {
        $user = User::factory()->create();
        $user->assignRole('premium');

        $this->assertEquals(10, $this->service->getMaxMediaPerPost($user));
        $this->assertEquals(51200, $this->service->getMaxFileSize($user));
        $this->assertEquals(500, $this->service->getPostsPerDayLimit($user));
        $this->assertEquals(600, $this->service->getMaxVideoLength($user));
        $this->assertEquals(100, $this->service->getScheduledPostsLimit($user));
        $this->assertTrue($this->service->canUploadHD($user));
        $this->assertFalse($this->service->canCreateAdvertisements($user));
    }

    /** @test */
    public function test_organization_role_gets_correct_limits()
    {
        $user = User::factory()->create();
        $user->assignRole('organization');

        $this->assertEquals(10, $this->service->getMaxMediaPerPost($user));
        $this->assertEquals(102400, $this->service->getMaxFileSize($user));
        $this->assertEquals(1000, $this->service->getPostsPerDayLimit($user));
        $this->assertEquals(500, $this->service->getScheduledPostsLimit($user));
        $this->assertTrue($this->service->canUploadHD($user));
        $this->assertTrue($this->service->canCreateAdvertisements($user));
    }

    /** @test */
    public function test_moderator_role_gets_correct_limits()
    {
        $user = User::factory()->create();
        $user->assignRole('moderator');

        $this->assertEquals(10, $this->service->getMaxMediaPerPost($user));
        $this->assertEquals(51200, $this->service->getMaxFileSize($user));
        $this->assertEquals(500, $this->service->getPostsPerDayLimit($user));
        $this->assertTrue($this->service->canUploadHD($user));
        $this->assertFalse($this->service->canCreateAdvertisements($user));
    }

    /** @test */
    public function test_admin_role_gets_correct_limits()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertEquals(20, $this->service->getMaxMediaPerPost($user));
        $this->assertEquals(204800, $this->service->getMaxFileSize($user));
        $this->assertEquals(2000, $this->service->getPostsPerDayLimit($user));
        $this->assertEquals(1200, $this->service->getMaxVideoLength($user));
        $this->assertEquals(1000, $this->service->getScheduledPostsLimit($user));
        $this->assertEquals(500, $this->service->getRateLimit($user));
        $this->assertTrue($this->service->canUploadHD($user));
        $this->assertTrue($this->service->canCreateAdvertisements($user));
    }

    /** @test */
    public function test_role_priority_admin_highest()
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'verified', 'admin']);

        $this->assertEquals('admin', $this->service->getUserHighestRole($user));
        $this->assertEquals(20, $this->service->getMaxMediaPerPost($user));
    }

    /** @test */
    public function test_role_priority_moderator_over_organization()
    {
        $user = User::factory()->create();
        $user->assignRole(['organization', 'moderator']);

        $this->assertEquals('moderator', $this->service->getUserHighestRole($user));
    }

    /** @test */
    public function test_fallback_to_user_limits()
    {
        $user = User::factory()->create();
        // No role assigned

        $this->assertEquals('user', $this->service->getUserHighestRole($user));
        $this->assertEquals(4, $this->service->getMaxMediaPerPost($user));
    }
}
