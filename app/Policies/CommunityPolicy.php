<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;
use App\Models\Post;

class CommunityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Community $community): bool
    {
        if ($community->privacy === 'public') {
            return true;
        }

        return $community->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Community $community): bool
    {
        $role = $community->getUserRole($user);
        return in_array($role, ['admin', 'owner']);
    }

    public function delete(User $user, Community $community): bool
    {
        return $community->getUserRole($user) === 'owner';
    }

    public function moderate(User $user, Community $community): bool
    {
        return $community->canUserModerate($user);
    }

    public function post(User $user, Community $community): bool
    {
        return $community->canUserPost($user);
    }

    public function pin(User $user, Community $community): bool
    {
        return $community->canUserModerate($user);
    }

    public function removeMember(User $auth, Community $community, User $target): bool
    {
        $authRole = $community->getUserRole($auth);
        $targetRole = $community->getUserRole($target);
        
        // Owner can remove anyone except themselves
        if ($authRole === 'owner' && $targetRole !== 'owner') return true;
        
        // Admin can remove moderator/member
        if ($authRole === 'admin' && in_array($targetRole, ['moderator', 'member'])) return true;
        
        // Moderator can remove member
        if ($authRole === 'moderator' && $targetRole === 'member') return true;
        
        return false;
    }

    public function updateRole(User $auth, Community $community, User $target): bool
    {
        $authRole = $community->getUserRole($auth);
        $targetRole = $community->getUserRole($target);
        
        // Cannot change owner role
        if ($targetRole === 'owner') return false;
        
        // Owner and admin can change roles
        return in_array($authRole, ['owner', 'admin']);
    }

    public function banMember(User $auth, Community $community, User $target): bool
    {
        $authRole = $community->getUserRole($auth);
        $targetRole = $community->getUserRole($target);
        
        // Cannot ban owner
        if ($targetRole === 'owner') return false;
        
        // Moderator, admin, owner can ban
        return in_array($authRole, ['moderator', 'admin', 'owner']);
    }

    public function transferOwnership(User $user, Community $community): bool
    {
        return $community->getUserRole($user) === 'owner';
    }

    public function removePost(User $user, Community $community, Post $post): bool
    {
        // Moderators can remove any post
        if ($community->canUserModerate($user)) return true;
        
        // Users can remove their own posts
        return $post->user_id === $user->id;
    }

    public function invite(User $user, Community $community): bool
    {
        // Members can create invites
        return $community->members()->where('user_id', $user->id)->exists();
    }
}