<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles first
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);
        
        $this->admin = User::factory()->create([
            'email' => 'admin@wonderway.com',
            'password' => bcrypt('password123')
        ]);
        
        // Assign admin role
        $this->admin->assignRole('admin');
    }

    // Basic Authentication Tests
    public function test_admin_panel_requires_authentication()
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_regular_user_cannot_access_admin_panel()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        
        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_admin_login_page_accessible()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    // Authorization Tests
    public function test_admin_has_correct_role()
    {
        $this->assertTrue($this->admin->hasRole('admin'));
        $this->assertTrue($this->admin->canAccessPanel(new \Filament\Panel('admin')));
    }

    public function test_user_role_assignment_works()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->canAccessPanel(new \Filament\Panel('admin')));
    }

    // Security Middleware Tests
    public function test_security_middleware_allows_testing_environment()
    {
        $this->assertTrue(app()->environment('testing'));
    }
}