<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SpacePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'space.create',
            'space.join',
            'space.leave',
            'space.manage.own',
            'space.delete.own',
            'space.update.own',
            'space.manage.roles',
            'space.end.own',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $userRole->givePermissionTo([
                'space.create',
                'space.join',
                'space.leave',
                'space.manage.own',
                'space.delete.own',
                'space.update.own',
                'space.manage.roles',
                'space.end.own',
            ]);
        }
    }
}
