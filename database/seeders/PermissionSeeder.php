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
        Permission::firstOrCreate(['name' => 'post.create', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'post.edit.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'post.delete.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'post.delete.any', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'post.schedule', 'guard_name' => 'sanctum']);

        // Comments
        Permission::firstOrCreate(['name' => 'comment.create', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'comment.delete.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'comment.delete.any', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'comment.like', 'guard_name' => 'sanctum']);

        // Messages
        Permission::firstOrCreate(['name' => 'message.send', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'message.delete.own', 'guard_name' => 'sanctum']);

        // Profile
        Permission::firstOrCreate(['name' => 'profile.edit.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'profile.view.private', 'guard_name' => 'sanctum']);

        // Follow
        Permission::firstOrCreate(['name' => 'user.follow', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'user.unfollow', 'guard_name' => 'sanctum']);

        // Interactions
        Permission::firstOrCreate(['name' => 'post.like', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'post.repost', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'post.bookmark', 'guard_name' => 'sanctum']);

        // Lists
        Permission::firstOrCreate(['name' => 'list.create', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'list.edit.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'list.delete.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'list.update.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'list.manage.members', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'list.subscribe', 'guard_name' => 'sanctum']);

        // Spaces
        Permission::firstOrCreate(['name' => 'space.create', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'space.host', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'space.speak', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'space.join', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'space.leave', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'space.manage.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'space.delete.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'space.update.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'space.manage.roles', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'space.end.own', 'guard_name' => 'sanctum']);

        // Communities
        Permission::firstOrCreate(['name' => 'community.create', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'community.moderate.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'community.post', 'guard_name' => 'sanctum']);

        // Moments
        Permission::firstOrCreate(['name' => 'moment.create', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'moment.edit.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'moment.delete.own', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'moment.manage.posts', 'guard_name' => 'sanctum']);

        // Realtime Features
        Permission::firstOrCreate(['name' => 'realtime.status.update', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'realtime.users.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'realtime.timeline.view', 'guard_name' => 'sanctum']);

        // Moderation
        Permission::firstOrCreate(['name' => 'user.ban', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'user.suspend', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'content.moderate', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'report.review', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'report.create', 'guard_name' => 'sanctum']);

        // Premium Features
        Permission::firstOrCreate(['name' => 'analytics.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'thread.create.long', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'media.upload.hd', 'guard_name' => 'sanctum']);

        // A/B Testing
        Permission::firstOrCreate(['name' => 'abtest.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'abtest.create', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'abtest.manage', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'abtest.delete', 'guard_name' => 'sanctum']);

        // Monetization - Advertisements
        Permission::firstOrCreate(['name' => 'advertisement.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'advertisement.create', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'advertisement.manage', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'advertisement.delete', 'guard_name' => 'sanctum']);

        // Monetization - Creator Fund
        Permission::firstOrCreate(['name' => 'creatorfund.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'creatorfund.payout', 'guard_name' => 'sanctum']);

        // Monetization - Premium
        Permission::firstOrCreate(['name' => 'premium.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'premium.subscribe', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'premium.cancel', 'guard_name' => 'sanctum']);

        // Performance & Monitoring
        Permission::firstOrCreate(['name' => 'performance.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'performance.optimize', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'performance.manage', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'monitoring.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'monitoring.errors', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'monitoring.manage', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'autoscaling.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'autoscaling.predict', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'autoscaling.manage', 'guard_name' => 'sanctum']);

        // Device Management
        Permission::firstOrCreate(['name' => 'device.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'device.register', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'device.trust', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'device.revoke', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'device.manage', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'device.security', 'guard_name' => 'sanctum']);

        // Polls
        Permission::firstOrCreate(['name' => 'poll.create', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'poll.vote', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'poll.delete.own', 'guard_name' => 'sanctum']);

        // Mentions
        Permission::firstOrCreate(['name' => 'mention.view', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'mention.create', 'guard_name' => 'sanctum']);

        // Media
        Permission::firstOrCreate(['name' => 'media.upload', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'media.delete', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'media.view', 'guard_name' => 'sanctum']);

        // Admin
        Permission::firstOrCreate(['name' => 'admin.panel.access', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'admin.users.manage', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'admin.settings.manage', 'guard_name' => 'sanctum']);

        // Clear cache after creating all permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Assign permissions to roles
        $user = Role::findByName('user', 'sanctum');
        $user->syncPermissions([
            'post.create', 'post.edit.own', 'post.delete.own',
            'comment.create', 'comment.delete.own', 'comment.like',
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
