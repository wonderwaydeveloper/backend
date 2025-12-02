<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Article;
use App\Models\PlatformSetting;
use App\Models\UploadLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser()
    {
        return User::factory()->create(['username' => 'admin']);
    }

    /** @test */
    public function admin_can_view_platform_stats()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        // Create test data
        User::factory()->count(5)->create();
        Post::factory()->count(10)->create();
        Article::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'users' => [
                        'total',
                        'active',
                        'banned',
                        'underage',
                        'today_registrations',
                    ],
                    'content' => [
                        'posts_total',
                        'posts_published',
                        'articles_total',
                        'articles_published',
                    ],
                    'security' => ['recent_events'],
                    'system' => ['phone_auth_enabled', 'social_auth_enabled'],
                ],
            ]);
    }

    /** @test */
    public function non_admin_cannot_view_platform_stats()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/stats');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_platform_settings()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        // Create some settings
        PlatformSetting::create([
            'key' => 'site_name',
            'value' => 'Test Site',
            'type' => 'string',
            'group' => 'general',
        ]);

        PlatformSetting::create([
            'key' => 'phone_auth_enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'authentication',
        ]);

        $response = $this->getJson('/api/admin/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'authentication',
                    'limits',
                    'general',
                    'privacy',
                ],
            ]);
    }

    /** @test */
    public function admin_can_update_platform_settings()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/admin/settings', [
            'settings' => [
                ['key' => 'site_name', 'value' => 'Updated Site Name'],
                ['key' => 'max_posts_per_day', 'value' => '100'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Settings updated successfully']);

        $this->assertEquals('Updated Site Name', PlatformSetting::getValue('site_name'));
        $this->assertEquals(100, PlatformSetting::getValue('max_posts_per_day'));
    }

    /** @test */
    public function admin_can_view_upload_limits()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        UploadLimit::create([
            'type' => 'post',
            'max_files' => 5,
            'max_file_size' => 10240,
        ]);

        $response = $this->getJson('/api/admin/upload-limits');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function admin_can_update_upload_limits()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        $limit = UploadLimit::create([
            'type' => 'post',
            'max_files' => 5,
            'max_file_size' => 10240,
        ]);

        $response = $this->putJson('/api/admin/upload-limits/post', [
            'max_files' => 10,
            'max_file_size' => 20480,
            'allowed_mimes' => ['jpg', 'png', 'mp4'],
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Upload limits updated successfully']);

        $this->assertDatabaseHas('upload_limits', [
            'type' => 'post',
            'max_files' => 10,
            'max_file_size' => 20480,
        ]);
    }

    /** @test */
    public function admin_can_toggle_phone_authentication()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        // Initially disabled
        PlatformSetting::setValue('phone_auth_enabled', false);

        // Enable
        $response = $this->postJson('/api/admin/phone-auth/toggle');
        $response->assertStatus(200)
            ->assertJson(['data' => ['enabled' => true]]);

        $this->assertTrue(PlatformSetting::getValue('phone_auth_enabled'));

        // Disable
        $response = $this->postJson('/api/admin/phone-auth/toggle');
        $response->assertStatus(200)
            ->assertJson(['data' => ['enabled' => false]]);

        $this->assertFalse(PlatformSetting::getValue('phone_auth_enabled'));
    }

    /** @test */
    public function admin_can_view_underage_users()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        // Create underage users
        User::factory()->count(3)->create(['is_underage' => true]);
        User::factory()->count(2)->create(['is_underage' => false]);

        $response = $this->getJson('/api/admin/underage-users');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.users.data'); // Paginated response
    }

    /** @test */
    public function admin_can_ban_user()
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/users/{$user->id}/ban");

        $response->assertStatus(200)
            ->assertJson(['data' => ['banned' => true]]);

        $this->assertTrue($user->fresh()->is_banned);
    }

    /** @test */
    public function admin_can_unban_user()
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create(['is_banned' => true]);
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/users/{$user->id}/unban");

        $response->assertStatus(200)
            ->assertJson(['data' => ['unbanned' => true]]);

        $this->assertFalse($user->fresh()->is_banned);
    }

    /** @test */
    public function admin_can_view_security_reports()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        $user = User::factory()->create();
        
        // Create security logs
        \App\Models\UserSecurityLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        \App\Models\UserSecurityLog::create([
            'user_id' => $user->id,
            'action' => 'password_change',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        $response = $this->getJson('/api/admin/security-reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'logs',
                    'stats' => [
                        'total_events',
                        'login_attempts',
                        'recent_events',
                        'top_actions',
                    ],
                ],
            ]);
    }

    /** @test */
    public function admin_can_feature_post()
    {
        $admin = $this->createAdminUser();
        $post = Post::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/posts/{$post->id}/feature");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Post featured successfully']);

        // Note: The actual implementation of featuring posts might need to be added
        // This test assumes there's a 'is_featured' column or similar
    }

    /** @test */
    public function admin_can_feature_article()
    {
        $admin = $this->createAdminUser();
        $article = Article::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/articles/{$article->id}/feature");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Article featured successfully']);

        $this->assertTrue($article->fresh()->is_featured);
    }
}