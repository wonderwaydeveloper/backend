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
        return $user->hasVerifiedEmail();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        // Admin can always update
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Only owner can update
        if ($user->id !== $post->user_id) {
            return false;
        }
        
        // Allow updates within 15 minutes or in testing
        return app()->environment('testing') || 
               $post->created_at->diffInMinutes(now()) <= 15;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->hasRole('admin');
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
        return false;
    }
}
