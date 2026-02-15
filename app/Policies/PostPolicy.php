<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Post $post): bool
    {
        // Public posts can be viewed by anyone
        if (!$post->is_private) {
            return true;
        }
        
        // Private posts only by owner or followers
        if (!$user) {
            return false;
        }
        
        return $user->id === $post->user_id || 
               $user->following()->where('following_id', $post->user_id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail() && $user->can('post.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        // Only owner can update
        if ($user->id !== $post->user_id) {
            return false;
        }
        
        // Check permission
        if (!$user->can('post.edit.own')) {
            return false;
        }
        
        // Check if post can be edited
        return $post->canBeEdited();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        // Can delete any post
        if ($user->can('post.delete.any')) {
            return true;
        }
        
        // Can delete own post
        return $user->id === $post->user_id && $user->can('post.delete.own');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return $user->can('post.delete.any');
    }
}
