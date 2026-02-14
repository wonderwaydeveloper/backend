<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};

class MentionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'mention.view',
            'mention.create',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // Assign to user role
        $userRole = Role::where('name', 'user')->where('guard_name', 'web')->first();
        if ($userRole) {
            $userRole->syncPermissions(
                Permission::where('guard_name', 'web')
                    ->whereIn('name', array_merge(
                        $userRole->permissions->pluck('name')->toArray(),
                        $permissions
                    ))
                    ->pluck('name')
                    ->toArray(),
                'web'
            );
        }
    }
}
