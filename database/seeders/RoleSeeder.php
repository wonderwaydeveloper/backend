<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Basic user role - Default role for all registered users
        Role::create([
            'name' => 'user',
            'guard_name' => 'sanctum'
        ]);

        // Verified user role - Users with verified email and identity
        Role::create([
            'name' => 'verified',
            'guard_name' => 'sanctum'
        ]);

        // Premium user role - Paid subscription with extended features
        Role::create([
            'name' => 'premium',
            'guard_name' => 'sanctum'
        ]);

        // Organization role - Business and enterprise accounts
        Role::create([
            'name' => 'organization',
            'guard_name' => 'sanctum'
        ]);

        // Moderator role - Content moderation and community management
        Role::create([
            'name' => 'moderator',
            'guard_name' => 'sanctum'
        ]);

        // Admin role - Full system access and management
        Role::create([
            'name' => 'admin',
            'guard_name' => 'sanctum'
        ]);
    }
}
