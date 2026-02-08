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
        Permission::create(['name' => 'post.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'post.edit.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'post.delete.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'post.delete.any', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'post.schedule', 'guard_name' => 'sanctum']);

        // Comments
        Permission::create(['name' => 'comment.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'comment.delete.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'comment.delete.any', 'guard_name' => 'sanctum']);

        // Messages
        Permission::create(['name' => 'message.send', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'message.delete.own', 'guard_name' => 'sanctum']);

        // Profile
        Permission::create(['name' => 'profile.edit.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'profile.view.private', 'guard_name' => 'sanctum']);

        // Follow
        Permission::create(['name' => 'user.follow', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'user.unfollow', 'guard_name' => 'sanctum']);

        // Interactions
        Permission::create(['name' => 'post.like', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'post.repost', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'post.bookmark', 'guard_name' => 'sanctum']);

        // Lists
        Permission::create(['name' => 'list.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'list.edit.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'list.delete.own', 'guard_name' => 'sanctum']);

        // Spaces
        Permission::create(['name' => 'space.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.host', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.speak', 'guard_name' => 'sanctum']);

        // Communities
        Permission::create(['name' => 'community.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'community.moderate.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'community.post', 'guard_name' => 'sanctum']);

        // Moderation
        Permission::create(['name' => 'user.ban', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'user.suspend', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'content.moderate', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'report.review', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'report.create', 'guard_name' => 'sanctum']);

        // Premium Features
        Permission::create(['name' => 'analytics.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'thread.create.long', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'media.upload.hd', 'guard_name' => 'sanctum']);

        // Admin
        Permission::create(['name' => 'admin.panel.access', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'admin.users.manage', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'admin.settings.manage', 'guard_name' => 'sanctum']);

        // Clear cache after creating all permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Assign permissions to roles
        $user = Role::findByName('user', 'sanctum');
        $user->syncPermissions([
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

        $verified = Role::findByName('verified', 'sanctum');
        $verified->syncPermissions([
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

        $premium = Role::findByName('premium', 'sanctum');
        $premium->syncPermissions([
            'post.create',
            'post.edit.own',
            'post.delete.own',
            'post.schedule',
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
            'space.create',
            'space.host',
            'space.speak',
            'community.create',
            'community.moderate.own',
            'community.post',
            'report.create',
            'analytics.view',
            'thread.create.long',
            'media.upload.hd',
        ]);

        $moderator = Role::findByName('moderator', 'sanctum');
        $moderator->syncPermissions([
            'post.create',
            'post.edit.own',
            'post.delete.own',
            'post.delete.any',
            'comment.create',
            'comment.delete.own',
            'comment.delete.any',
            'message.send',
            'message.delete.own',
            'profile.edit.own',
            'user.follow',
            'user.unfollow',
            'user.suspend',
            'post.like',
            'post.repost',
            'post.bookmark',
            'list.create',
            'list.edit.own',
            'list.delete.own',
            'community.post',
            'content.moderate',
            'report.review',
            'report.create',
        ]);

        $admin = Role::findByName('admin', 'sanctum');
        $admin->syncPermissions(Permission::where('guard_name', 'sanctum')->pluck('name'));
    }
}
