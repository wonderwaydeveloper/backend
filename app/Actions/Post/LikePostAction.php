<?php

namespace App\Actions\Post;

use App\Models\Post;
use App\Models\User;
use App\Models\Like;

class LikePostAction
{
    public function execute(Post $post, User $user): array
    {
        $like = Like::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($like) {
            $like->delete();
            return ['liked' => false, 'likes_count' => $post->likes()->count()];
        }

        Like::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        return ['liked' => true, 'likes_count' => $post->likes()->count()];
    }
}