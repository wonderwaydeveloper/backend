<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true; // Comments are public
    }

    public function view(?User $user, Comment $comment): bool
    {
        // Public comments are visible to everyone
        $post = $comment->post;
        
        // If post is private, only followers can see
        if ($post && $post->user && $post->user->is_private) {
            if (!$user) return false;
            if ($user->id === $post->user_id) return true;
            return $post->user->followers()->where('follower_id', $user->id)->exists();
        }
        
        // Check if viewer is blocked
        if ($user && $comment->user) {
            if ($comment->user->blockedUsers()->where('blocked_user_id', $user->id)->exists()) {
                return false;
            }
        }
        
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->can('comment.create');
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        // Can delete any comment
        if ($user->can('comment.delete.any')) {
            return true;
        }
        
        // Can delete own comment
        return $user->id === $comment->user_id && $user->can('comment.delete.own');
    }

    public function restore(User $user, Comment $comment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Comment $comment): bool
    {
        return false;
    }
}
