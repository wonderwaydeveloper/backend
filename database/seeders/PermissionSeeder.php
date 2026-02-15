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
        Permission::create(['name' => 'list.update.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'list.manage.members', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'list.subscribe', 'guard_name' => 'sanctum']);

        // Spaces
        Permission::create(['name' => 'space.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.host', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.speak', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.join', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.leave', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.manage.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.delete.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.update.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.manage.roles', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'space.end.own', 'guard_name' => 'sanctum']);

        // Communities
        Permission::create(['name' => 'community.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'community.moderate.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'community.post', 'guard_name' => 'sanctum']);

        // Moments
        Permission::create(['name' => 'moment.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'moment.edit.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'moment.delete.own', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'moment.manage.posts', 'guard_name' => 'sanctum']);

        // Realtime Features
        Permission::create(['name' => 'realtime.status.update', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'realtime.users.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'realtime.timeline.view', 'guard_name' => 'sanctum']);

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

        // A/B Testing
        Permission::create(['name' => 'abtest.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'abtest.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'abtest.manage', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'abtest.delete', 'guard_name' => 'sanctum']);

        // Monetization - Advertisements
        Permission::create(['name' => 'advertisement.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'advertisement.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'advertisement.manage', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'advertisement.delete', 'guard_name' => 'sanctum']);

        // Monetization - Creator Fund
        Permission::create(['name' => 'creatorfund.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'creatorfund.payout', 'guard_name' => 'sanctum']);

        // Monetization - Premium
        Permission::create(['name' => 'premium.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'premium.subscribe', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'premium.cancel', 'guard_name' => 'sanctum']);

        // Performance & Monitoring
        Permission::create(['name' => 'performance.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'performance.optimize', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'performance.manage', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'monitoring.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'monitoring.errors', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'monitoring.manage', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'autoscaling.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'autoscaling.predict', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'autoscaling.manage', 'guard_name' => 'sanctum']);

        // Device Management
        Permission::create(['name' => 'device.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'device.register', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'device.trust', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'device.revoke', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'device.manage', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'device.security', 'guard_name' => 'sanctum']);

// Polls
        Permission::create(['name' => 'poll.create', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'poll.vote', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'poll.delete.own', 'guard_name' => 'sanctum']);

        // Mentions
        Permission::create(['name' => 'mention.view', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'mention.create', 'guard_name' => 'sanctum']);

        // Media
        Permission::create(['name' => 'media.upload', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'media.delete', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'media.view', 'guard_name' => 'sanctum']);

        // Admin
        Permission::create(['name' => 'admin.panel.access', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'admin.users.manage', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'admin.settings.manage', 'guard_name' => 'sanctum']);

        // Clear cache after creating all permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Assign permissions to roles
        $user = Role::findByName('user', 'sanctum');
        $user->syncPermissions([
            'post.create', 'post.edit.own', 'post.delete.own',
            'comment.create', 'comment.delete.own',
            'message.send', 'message.delete.own',
            'profile.edit.own', 'user.follow', 'user.unfollow',
            'post.like', 'post.repost', 'post.bookmark',
            'poll.create', 'poll.vote', 'poll.delete.own',
            'mention.view', 'mention.create',
            'media.upload', 'media.delete', 'media.view',
            'realtime.status.update', 'realtime.users.view', 'realtime.timeline.view',
            'report.create',
            'device.view', 'device.register',
        ]);

        $verified = Role::findByName('verified', 'sanctum');
        $verified->syncPermissions([
            'post.create', 'post.edit.own', 'post.delete.own',
            'comment.create', 'comment.delete.own',
            'message.send', 'message.delete.own',
            'profile.edit.own', 'user.follow', 'user.unfollow',
            'post.like', 'post.repost', 'post.bookmark',
            'list.create', 'list.edit.own', 'list.delete.own', 'list.update.own', 'list.manage.members', 'list.subscribe',
            'space.join', 'space.leave',
            'poll.create', 'poll.vote', 'poll.delete.own',
            'mention.view', 'mention.create',
            'media.upload', 'media.delete', 'media.view',
            'community.post',
            'moment.create', 'moment.edit.own', 'moment.delete.own', 'moment.manage.posts',
            'realtime.status.update', 'realtime.users.view', 'realtime.timeline.view',
            'report.create',
            'creatorfund.view', 'creatorfund.payout',
            'device.view', 'device.register', 'device.trust', 'device.revoke',
        ]);

        $premium = Role::findByName('premium', 'sanctum');
        $premium->syncPermissions([
            'post.create', 'post.edit.own', 'post.delete.own', 'post.schedule',
            'comment.create', 'comment.delete.own',
            'message.send', 'message.delete.own',
            'profile.edit.own', 'user.follow', 'user.unfollow',
            'post.like', 'post.repost', 'post.bookmark',
            'list.create', 'list.edit.own', 'list.delete.own', 'list.update.own', 'list.manage.members', 'list.subscribe',
            'space.create', 'space.host', 'space.speak', 'space.join', 'space.leave', 'space.manage.own', 'space.delete.own', 'space.update.own', 'space.manage.roles', 'space.end.own',
            'poll.create', 'poll.vote', 'poll.delete.own',
            'mention.view', 'mention.create',
            'media.upload', 'media.delete', 'media.view', 'media.upload.hd',
            'community.create', 'community.moderate.own', 'community.post',
            'moment.create', 'moment.edit.own', 'moment.delete.own', 'moment.manage.posts',
            'realtime.status.update', 'realtime.users.view', 'realtime.timeline.view',
            'report.create',
            'analytics.view', 'thread.create.long',
            'creatorfund.view', 'creatorfund.payout',
            'premium.view', 'premium.subscribe', 'premium.cancel',
            'device.view', 'device.register', 'device.trust', 'device.revoke', 'device.manage', 'device.security',
        ]);

        $organization = Role::findByName('organization', 'sanctum');
        $organization->syncPermissions([
            'post.create', 'post.edit.own', 'post.delete.own', 'post.schedule',
            'comment.create', 'comment.delete.own',
            'message.send', 'message.delete.own',
            'profile.edit.own', 'user.follow', 'user.unfollow',
            'post.like', 'post.repost', 'post.bookmark',
            'list.create', 'list.edit.own', 'list.delete.own', 'list.update.own', 'list.manage.members', 'list.subscribe',
            'space.create', 'space.host', 'space.speak', 'space.join', 'space.leave', 'space.manage.own', 'space.delete.own', 'space.update.own', 'space.manage.roles', 'space.end.own',
            'poll.create', 'poll.vote', 'poll.delete.own',
            'mention.view', 'mention.create',
            'media.upload', 'media.delete', 'media.view', 'media.upload.hd',
            'community.create', 'community.moderate.own', 'community.post',
            'moment.create', 'moment.edit.own', 'moment.delete.own', 'moment.manage.posts',
            'realtime.status.update', 'realtime.users.view', 'realtime.timeline.view',
            'report.create',
            'analytics.view', 'thread.create.long',
            'advertisement.view', 'advertisement.create', 'advertisement.manage', 'advertisement.delete',
            'device.view', 'device.register', 'device.trust', 'device.revoke', 'device.manage', 'device.security',
        ]);

        $moderator = Role::findByName('moderator', 'sanctum');
        $moderator->syncPermissions([
            'post.create', 'post.edit.own', 'post.delete.own', 'post.delete.any',
            'comment.create', 'comment.delete.own', 'comment.delete.any',
            'message.send', 'message.delete.own',
            'profile.edit.own', 'user.follow', 'user.unfollow',
            'post.like', 'post.repost', 'post.bookmark',
            'list.create', 'list.edit.own', 'list.delete.own', 'list.update.own', 'list.manage.members', 'list.subscribe',
            'space.join', 'space.leave',
            'poll.create', 'poll.vote', 'poll.delete.own',
            'mention.view', 'mention.create',
            'media.upload', 'media.delete', 'media.view',
            'community.post',
            'moment.create', 'moment.edit.own', 'moment.delete.own', 'moment.manage.posts',
            'realtime.status.update', 'realtime.users.view', 'realtime.timeline.view',
            'user.ban', 'user.suspend', 'content.moderate', 'report.review', 'report.create',
            'device.view', 'device.register', 'device.trust', 'device.revoke',
        ]);

        $admin = Role::findByName('admin', 'sanctum');
        $admin->syncPermissions(Permission::where('guard_name', 'sanctum')->pluck('name'));

        echo "\nDevice Management permissions created successfully!\n";
    }
}
