<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Posts
        Permission::create(['name' => 'post.create']);
        Permission::create(['name' => 'post.edit.own']);
        Permission::create(['name' => 'post.delete.own']);
        Permission::create(['name' => 'post.delete.any']);
        Permission::create(['name' => 'post.schedule']);

        // Comments
        Permission::create(['name' => 'comment.create']);
        Permission::create(['name' => 'comment.delete.own']);
        Permission::create(['name' => 'comment.delete.any']);

        // Messages
        Permission::create(['name' => 'message.send']);
        Permission::create(['name' => 'message.delete.own']);

        // Profile
        Permission::create(['name' => 'profile.edit.own']);
        Permission::create(['name' => 'profile.view.private']);

        // Follow
        Permission::create(['name' => 'user.follow']);
        Permission::create(['name' => 'user.unfollow']);

        // Interactions
        Permission::create(['name' => 'post.like']);
        Permission::create(['name' => 'post.repost']);
        Permission::create(['name' => 'post.bookmark']);

        // Lists
        Permission::create(['name' => 'list.create']);
        Permission::create(['name' => 'list.edit.own']);
        Permission::create(['name' => 'list.delete.own']);

        // Spaces
        Permission::create(['name' => 'space.create']);
        Permission::create(['name' => 'space.host']);
        Permission::create(['name' => 'space.speak']);

        // Communities
        Permission::create(['name' => 'community.create']);
        Permission::create(['name' => 'community.moderate.own']);
        Permission::create(['name' => 'community.post']);

        // Moderation
        Permission::create(['name' => 'user.ban']);
        Permission::create(['name' => 'user.suspend']);
        Permission::create(['name' => 'content.moderate']);
        Permission::create(['name' => 'report.review']);
        Permission::create(['name' => 'report.create']);

        // Premium Features
        Permission::create(['name' => 'analytics.view']);
        Permission::create(['name' => 'thread.create.long']);
        Permission::create(['name' => 'media.upload.hd']);

        // Admin
        Permission::create(['name' => 'admin.panel.access']);
        Permission::create(['name' => 'admin.users.manage']);
        Permission::create(['name' => 'admin.settings.manage']);

        // Assign permissions to roles
        $user = Role::findByName('user');
        $user->givePermissionTo([
            'post.create',
            'post.edit.own',
            'post.delete.own',
            'comment.create',
            'comment.delete.own',
            'message.send',
            'message.delete.own',
            'profile.edit.own',
            'user.follow',
            'user.unfollow',
            'post.like',
            'post.repost',
            'post.bookmark',
            'list.create',
            'list.edit.own',
            'list.delete.own',
            'community.post',
            'report.create',
        ]);

        $admin = Role::findByName('admin');
        $admin->givePermissionTo(Permission::all());
    }
}
