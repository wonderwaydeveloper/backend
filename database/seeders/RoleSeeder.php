<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'user']);
        Role::create(['name' => 'verified']);
        Role::create(['name' => 'premium']);
        Role::create(['name' => 'moderator']);
        Role::create(['name' => 'admin']);
    }
}
