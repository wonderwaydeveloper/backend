<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'user', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'verified', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'premium', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'moderator', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
    }
}
