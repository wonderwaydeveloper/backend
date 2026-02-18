<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Basic user role - Default role for all registered users
        Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'sanctum']
        );

        // Verified user role - Users with verified email and identity
        Role::firstOrCreate(
            ['name' => 'verified', 'guard_name' => 'sanctum']
        );

        // Premium user role - Paid subscription with extended features
        Role::firstOrCreate(
            ['name' => 'premium', 'guard_name' => 'sanctum']
        );

        // Organization role - Business and enterprise accounts
        Role::firstOrCreate(
            ['name' => 'organization', 'guard_name' => 'sanctum']
        );

        // Moderator role - Content moderation and community management
        Role::firstOrCreate(
            ['name' => 'moderator', 'guard_name' => 'sanctum']
        );

        // Admin role - Full system access and management
        Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'sanctum']
        );
    }
}
