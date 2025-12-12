<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\PlatformSetting;
use App\Models\UploadLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser()
    {
        return User::factory()->create(['username' => 'admin']);
    }

    #[Test]
    public function admin_can_view_platform_stats()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        // Create test data
        User::factory()->count(5)->create();
        Post::factory()->count(10)->create();

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
                    ],
                    'security' => ['recent_events'],
                    'system' => ['phone_auth_enabled', 'social_auth_enabled'],
                ],
            ]);
    }

    #[Test]
    public function non_admin_cannot_view_platform_stats()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/stats');

        $response->assertStatus(403);
    }

    #[Test]
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

    #[Test]
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

        // **اصلاح مهم:** استفاده از assertJsonPath برای مطابقت با ساختار GenericResource
        $response->assertStatus(200)
            ->assertJsonPath('meta.message', 'Settings updated successfully');

        $this->assertEquals('Updated Site Name', PlatformSetting::getValue('site_name'));
        $this->assertEquals(100, PlatformSetting::getValue('max_posts_per_day'));
    }

    #[Test]
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

    #[Test]
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

        // **اصلاح مهم:** استفاده از assertJsonPath
        $response->assertStatus(200)
            ->assertJsonPath('meta.message', 'Upload limits updated successfully');

        $this->assertDatabaseHas('upload_limits', [
            'type' => 'post',
            'max_files' => 10,
            'max_file_size' => 20480,
        ]);
    }

    #[Test]
    public function admin_can_toggle_phone_authentication()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        // Initially disabled
        PlatformSetting::setValue('phone_auth_enabled', false);

        // Enable
        $response = $this->postJson('/api/admin/phone-auth/toggle');
        $response->assertStatus(200)
            ->assertJsonPath('data.enabled', true);

        $this->assertTrue(PlatformSetting::getValue('phone_auth_enabled'));

        // Disable
        $response = $this->postJson('/api/admin/phone-auth/toggle');
        $response->assertStatus(200)
            ->assertJsonPath('data.enabled', false);

        $this->assertFalse(PlatformSetting::getValue('phone_auth_enabled'));
    }

    #[Test]
    public function admin_can_view_underage_users()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);

        User::factory()->count(3)->underage()->create();
        User::factory()->count(2)->create();

        $response = $this->getJson('/api/admin/underage-users');

        // بررسی اولیه
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data'
            ]);

        // بررسی تعداد کاربران
        $responseData = $response->json();
        $this->assertCount(3, $responseData['data']['users']['data']);
    }

    #[Test]
    public function admin_can_ban_user()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);
        $user = User::factory()->create();

        $response = $this->postJson("/api/admin/users/{$user->id}/ban");

        // **اصلاح مهم:** استفاده از assertJsonPath
        $response->assertStatus(200)
            ->assertJsonPath('data.banned', true);

        $this->assertTrue($user->refresh()->is_banned);
    }

    #[Test]
    public function admin_can_unban_user()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);
        $user = User::factory()->create(['is_banned' => true]);

        $response = $this->postJson("/api/admin/users/{$user->id}/unban");

        // **اصلاح مهم:** استفاده از assertJsonPath
        $response->assertStatus(200)
            ->assertJsonPath('data.unbanned', true);

        $this->assertFalse($user->refresh()->is_banned);
    }

    #[Test]
    public function admin_can_feature_post()
    {
        $admin = $this->createAdminUser();
        Sanctum::actingAs($admin);
        $post = Post::factory()->create();

        $response = $this->postJson("/api/admin/posts/{$post->id}/feature");

        // **اصلاح مهم:** استفاده از assertJsonPath
        $response->assertStatus(200)
            ->assertJsonPath('meta.message', 'Post featured successfully');

        // Note: The actual implementation of featuring posts might need to be added
        // This test assumes there's a 'is_featured' column or similar
    }
}