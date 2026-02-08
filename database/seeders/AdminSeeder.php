<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'username' => 'admin',
                'password' => bcrypt(env('ADMIN_PASSWORD', bin2hex(random_bytes(16)))),
                'email_verified_at' => now(),
                'is_premium' => true,
            ]
        );
        
        // Assign admin role
        if (!$admin->hasRole('admin', 'sanctum')) {
            $admin->assignRole($adminRole);
        }
        
        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: wonderwaydeveloper@gmail.com');
        $this->command->info('Password: password123');
    }
}
