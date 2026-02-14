<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PollPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'poll.create',
            'poll.vote',
            'poll.delete.own',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $permissionModels = Permission::whereIn('name', $permissions)->get();
            $userRole->permissions()->syncWithoutDetaching($permissionModels);
        }

        $this->command->info('Poll permissions created successfully!');
    }
}
