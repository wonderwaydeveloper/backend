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
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'wonderwaydeveloper@gmail.com'],
            [
                'name' => 'Vahid Pahnavar',
                'username' => 'admin',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
                'is_premium' => true,
            ]
        );
        
        // Assign admin role
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
        
        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: wonderwaydeveloper@gmail.com');
        $this->command->info('Password: password123');
    }
}
