<?php

namespace App\Services;

use App\Contracts\PostServiceInterface;
use App\Contracts\PostRepositoryInterface;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class PostService implements PostServiceInterface
{
    public function __construct(
        private PostRepositoryInterface $postRepository
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

        $this->clearPostCaches($post->user_id);

        return $post;
    }

    public function deletePost(Post $post): bool
    {
        if ($post->image) {
            Storage::disk('public')->delete($post->image);
        }

        $result = $this->postRepository->delete($post);
        
        if ($result) {
            $this->clearPostCaches($post->user_id);
        }
        
        return $result;
    }

    public function toggleLike(Post $post, int $userId): array
    {
        if ($post->isLikedBy($userId)) {
            $post->likes()->where('user_id', $userId)->delete();
            $post->decrement('likes_count');
            $liked = false;
        } else {
            $post->likes()->create(['user_id' => $userId]);
            $post->increment('likes_count');
            $liked = true;
        }
        
        return ['liked' => $liked, 'likes_count' => $post->likes_count];
    }
    
    public function getTimeline(int $userId, int $limit = 20): array
    {
        return $this->postRepository->getTimeline($userId, $limit);
    }
    
    public function getUserPosts(int $userId, int $limit = 20): array
    {
        $cacheKey = "user_posts:{$userId}:{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId, $limit) {
            return $this->postRepository->getUserPosts($userId, $limit)->toArray();
        });
    }
    
    public function searchPosts(string $query, array $filters = []): array
    {
        return $this->postRepository->searchPosts($query, $filters);
    }
    
    private function clearPostCaches(int $userId): void
    {
        Cache::forget("user_posts:{$userId}:20");
        Cache::forget("posts:public:page:1");
        Cache::tags(['posts', "user:{$userId}"])->flush();
    }
}
