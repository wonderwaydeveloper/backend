<?php

namespace App\Actions\Post;

use App\Models\Post;

class UpdatePostAction
{
    public function execute(Post $post, array $data): Post
    {
        $post->update([
            'content' => $data['content'] ?? $post->content,
            'media_url' => $data['media_url'] ?? $post->media_url,
            'media_type' => $data['media_type'] ?? $post->media_type,
        ]);

        return $post->fresh();
    }
}