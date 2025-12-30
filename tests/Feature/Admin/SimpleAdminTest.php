<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_accessible()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_redirected_to_login()
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_role_exists()
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);
        
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->canAccessPanel(new \Filament\Panel('admin')));
    }

    public function test_regular_user_cannot_access_panel()
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);
        
        $user = User::factory()->create();
        $user->assignRole('user');
        
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->canAccessPanel(new \Filament\Panel('admin')));
    }
}