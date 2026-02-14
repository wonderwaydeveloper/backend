<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ListPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'list.create',
            'list.update.own',
            'list.delete.own',
            'list.manage.members',
            'list.subscribe',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign to user role
        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $permissionModels = Permission::whereIn('name', $permissions)->get();
            $userRole->permissions()->syncWithoutDetaching($permissionModels);
        }

        $this->command->info('List permissions created successfully!');
    }
}
