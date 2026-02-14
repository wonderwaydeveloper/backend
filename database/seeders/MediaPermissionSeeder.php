<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MediaPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'media.upload',
            'media.delete',
            'media.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }
    }
}
