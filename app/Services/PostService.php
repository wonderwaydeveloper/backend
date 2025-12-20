<?php

namespace App\Services;

use App\Models\Post;
use App\Repositories\PostRepository;
use Illuminate\Support\Facades\Storage;

class PostService
{
    public function __construct(
        private PostRepository $postRepository
    ) {}

    public function createPost(array $data, $imageFile = null, bool $isDraft = false): Post
    {
        if ($imageFile) {
            $data['image'] = $imageFile->store('posts', 'public');
        }

        $data['is_draft'] = $isDraft;
        $data['published_at'] = $isDraft ? null : now();

        $post = $this->postRepository->create($data);
        $post->syncHashtags();
        $post->load('user:id,name,username,avatar', 'hashtags');

        return $post;
    }

    public function deletePost(Post $post): bool
    {
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }

        return $this->postRepository->delete($post);
    }

    public function toggleLike(Post $post, int $userId): array
    {
        if ($post->isLikedBy($userId)) {
            $post->likes()->where('user_id', $userId)->delete();
            $post->decrement('likes_count');
            return ['liked' => false, 'likes_count' => $post->likes_count];
        }

        $post->likes()->create(['user_id' => $userId]);
        $post->increment('likes_count');
        return ['liked' => true, 'likes_count' => $post->likes_count];
    }
}
