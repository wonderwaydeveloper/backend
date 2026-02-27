<?php

namespace App\Services;

use App\Models\Community;
use App\Models\User;
use App\Models\Post;
use App\Models\CommunityBan;
use App\Models\CommunityInvite;
use App\Models\CommunityJoinRequest;
use App\Events\MemberRemoved;
use App\Events\MemberRoleUpdated;
use App\Events\MemberBanned;
use App\Events\OwnershipTransferred;
use App\Events\PostRemovedFromCommunity;
use App\Events\MemberJoined;
use App\Events\CommunityCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommunityService
{
    public function createCommunity(array $data, User $creator): Community
    {
        $slug = Str::slug($data['name']);
        $originalSlug = $slug;
        $counter = 1;
        
        while (Community::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        $community = DB::transaction(function() use ($data, $creator, $slug) {
            $community = Community::create([
                ...$data,
                'created_by' => $creator->id,
                'slug' => $slug,
            ]);

            $community->members()->attach($creator->id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            $community->incrementMemberCount();

            return $community;
        });

        event(new CommunityCreated($community));

        return $community;
    }

    public function joinCommunity(Community $community, User $user): array
    {
        // Check if banned
        if ($community->isBanned($user)) {
            return [
                'message' => 'You are banned from this community',
                'status' => 403
            ];
        }

        // Check if already member
        if (!$community->canUserJoin($user)) {
            return [
                'message' => 'Already a member',
                'status' => 400
            ];
        }

        // Private community - create join request
        if ($community->privacy === 'private') {
            $existingRequest = CommunityJoinRequest::where([
                'community_id' => $community->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ])->first();

            if ($existingRequest) {
                return [
                    'message' => 'Join request already sent',
                    'status' => 400
                ];
            }

            CommunityJoinRequest::where([
                'community_id' => $community->id,
                'user_id' => $user->id,
            ])->whereIn('status', ['rejected', 'approved'])->delete();

            CommunityJoinRequest::create([
                'community_id' => $community->id,
                'user_id' => $user->id,
            ]);

            return ['message' => 'Join request sent'];
        }

        // Public community - join directly
        DB::transaction(function() use ($community, $user) {
            $community->members()->attach($user->id, [
                'role' => 'member',
                'joined_at' => now(),
            ]);

            $community->incrementMemberCount();
        });

        event(new MemberJoined($community, $user));

        return ['message' => 'Joined successfully'];
    }

    public function leaveCommunity(Community $community, User $user): void
    {
        $role = $community->getUserRole($user);

        if (!$role) {
            throw new \Exception('Not a member');
        }

        if ($role === 'owner') {
            throw new \Exception('Owner cannot leave community');
        }

        DB::transaction(function() use ($community, $user) {
            $community->members()->detach($user->id);
            $community->decrementMemberCount();
        });
    }

    public function approveJoinRequest(CommunityJoinRequest $joinRequest, User $approver): void
    {
        $joinRequest->approve($approver);
    }

    public function rejectJoinRequest(CommunityJoinRequest $joinRequest, User $rejector): void
    {
        $joinRequest->reject($rejector);
    }
    public function removeMember(Community $community, User $user): void
    {
        DB::transaction(function() use ($community, $user) {
            $community->members()->detach($user->id);
            $community->decrementMemberCount();
        });

        event(new MemberRemoved($community, $user, auth()->user()));
    }

    public function updateMemberRole(Community $community, User $user, string $newRole): string
    {
        $oldRole = $community->getUserRole($user);

        DB::transaction(function() use ($community, $user, $newRole) {
            $community->members()->updateExistingPivot($user->id, [
                'role' => $newRole
            ]);
        });

        event(new MemberRoleUpdated($community, $user, $oldRole, $newRole));

        return $oldRole;
    }

    public function banMember(Community $community, User $user, ?string $reason, ?int $duration): void
    {
        $expiresAt = $duration ? now()->addDays($duration) : null;

        DB::transaction(function() use ($community, $user, $reason, $expiresAt) {
            $community->members()->detach($user->id);
            $community->decrementMemberCount();

            CommunityBan::create([
                'community_id' => $community->id,
                'user_id' => $user->id,
                'banned_by' => auth()->id(),
                'reason' => $reason,
                'banned_at' => now(),
                'expires_at' => $expiresAt,
            ]);
        });

        event(new MemberBanned($community, $user, $reason, $expiresAt));
    }

    public function unbanMember(Community $community, User $user): void
    {
        CommunityBan::where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function transferOwnership(Community $community, User $newOwner, User $currentOwner): void
    {
        if (!$community->members()->where('user_id', $newOwner->id)->exists()) {
            throw new \Exception('User must be a member');
        }

        DB::transaction(function() use ($community, $newOwner, $currentOwner) {
            $community->members()->updateExistingPivot($currentOwner->id, ['role' => 'admin']);
            $community->members()->updateExistingPivot($newOwner->id, ['role' => 'owner']);
        });

        event(new OwnershipTransferred($community, $currentOwner, $newOwner));
    }

    public function pinPost(Community $community, Post $post): void
    {
        if ($post->community_id !== $community->id) {
            throw new \Exception('Post does not belong to this community');
        }

        if ($community->pinnedPosts()->count() >= 3) {
            throw new \Exception('Maximum 3 pinned posts allowed');
        }

        $post->update([
            'is_pinned_in_community' => true,
            'pinned_at' => now(),
            'pinned_by' => auth()->id(),
        ]);
    }

    public function unpinPost(Community $community, Post $post): void
    {
        if ($post->community_id !== $community->id) {
            throw new \Exception('Post does not belong to this community');
        }

        $post->update([
            'is_pinned_in_community' => false,
            'pinned_at' => null,
            'pinned_by' => null,
        ]);
    }

    public function removePost(Community $community, Post $post): void
    {
        if ($post->community_id !== $community->id) {
            throw new \Exception('Post does not belong to this community');
        }

        DB::transaction(function() use ($community, $post) {
            $post->update(['community_id' => null]);
            $community->decrementPostCount();
        });

        event(new PostRemovedFromCommunity($community, $post));
    }

    public function muteCommunity(Community $community, User $user): void
    {
        DB::table('community_mutes')->insertOrIgnore([
            'community_id' => $community->id,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function unmuteCommunity(Community $community, User $user): void
    {
        DB::table('community_mutes')
            ->where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function getNotificationSettings(Community $community, User $user): array
    {
        $member = $community->members()->where('user_id', $user->id)->first();
        
        if (!$member) {
            throw new \Exception('Not a member');
        }
        
        return $member->pivot->notification_settings ?? [
            'new_posts' => true,
            'new_members' => false,
            'role_changes' => true,
            'mentions' => true,
            'announcements' => true,
        ];
    }

    public function updateNotificationSettings(Community $community, User $user, array $settings): void
    {
        $member = $community->members()->where('user_id', $user->id)->first();
        
        if (!$member) {
            throw new \Exception('Not a member');
        }

        $community->members()->updateExistingPivot($user->id, [
            'notification_settings' => $settings
        ]);
    }

    public function createInvite(Community $community, int $maxUses = 1, ?int $expiresInDays = null): CommunityInvite
    {
        return CommunityInvite::create([
            'community_id' => $community->id,
            'invited_by' => auth()->id(),
            'invite_code' => Str::random(10),
            'max_uses' => $maxUses,
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
        ]);
    }

    public function deleteInvite(Community $community, string $code, User $user): void
    {
        CommunityInvite::where('community_id', $community->id)
            ->where('invite_code', $code)
            ->where('invited_by', $user->id)
            ->delete();
    }

    public function joinWithCode(string $code, User $user): Community
    {
        $invite = CommunityInvite::where('invite_code', $code)->firstOrFail();

        if (!$invite->isValid()) {
            throw new \Exception('Invite code expired or invalid');
        }

        $community = $invite->community()->first();

        // Check membership with fresh query
        $isMember = DB::table('community_members')
            ->where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->exists();
            
        if ($isMember) {
            throw new \Exception('Already a member');
        }

        if ($community->isBanned($user)) {
            throw new \Exception('You are banned from this community');
        }

        DB::transaction(function() use ($community, $user, $invite) {
            $community->members()->attach($user->id, [
                'role' => 'member',
                'joined_at' => now(),
            ]);

            $community->incrementMemberCount();
            $invite->increment('uses');
        });

        event(new MemberJoined($community, $user));

        return $community;
    }
}
