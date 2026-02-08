<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Events\PostInteraction;
use Illuminate\Support\Facades\DB;

class PostLikeService
{
    public function toggleLike(Post $post, User $user): array
    {
        return DB::transaction(function () use ($post, $user) {
            $post = Post::lockForUpdate()->findOrFail($post->id);
            
            if ($post->isLikedBy($user->id)) {
                return $this->unlikePost($post, $user);
            }
            
            return $this->likePost($post, $user);
        });
    }

    private function likePost(Post $post, User $user): array
    {
        $post->likes()->create(['user_id' => $user->id]);
        $post->increment('likes_count');
        
        event(new \App\Events\PostLiked($post, $user));
        broadcast(new PostInteraction($post, 'like', $user, ['liked' => true]));
        
        return ['liked' => true, 'likes_count' => $post->likes_count];
    }

    private function unlikePost(Post $post, User $user): array
    {
        $post->likes()->where('user_id', $user->id)->delete();
        if ($post->likes_count > 0) {
            $post->decrement('likes_count');
        }
        
        broadcast(new PostInteraction($post, 'like', $user, ['liked' => false]));
        
        return ['liked' => false, 'likes_count' => $post->likes_count];
    }
}