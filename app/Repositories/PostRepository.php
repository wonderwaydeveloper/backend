<?php

namespace App\Repositories;

use App\Contracts\PostRepositoryInterface;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PostRepository implements PostRepositoryInterface
{
    public function create(array $data): Post
    {
        return Post::create($data);
    }
    
    public function findById(int $id): ?Post
    {
        return Post::find($id);
    }
    
    public function findWithRelations(int $id, array $relations = []): ?Post
    {
        return Post::with($relations)->find($id);
    }
    
    public function update(Post $post, array $data): Post
    {
        $post->update($data);
        return $post->fresh();
    }
    
    public function delete(Post $post): bool
    {
        return $post->delete();
    }
    
    public function getPublicPosts(int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return Post::published()
            ->with([
                'user:id,name,username,avatar',
                'hashtags:id,name,slug',
                'poll.options',
                'quotedPost.user:id,name,username,avatar',
                'threadPosts.user:id,name,username,avatar'
            ])
            ->withCount('likes', 'comments', 'quotes')
            ->whereNull('thread_id')
            ->latest('published_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
    
    public function getTimelinePosts(int $userId, int $limit = 20): Collection
    {
        $followingIds = $this->getFollowingIds($userId);
        
        return Post::with(['user:id,name,username,avatar', 'likes:id,post_id'])
            ->select(['id', 'user_id', 'content', 'created_at', 'likes_count', 'comments_count'])
            ->whereIn('user_id', $followingIds)
            ->published()
            ->latest()
            ->limit($limit)
            ->get();
    }
    
    public function getUserDrafts(int $userId): LengthAwarePaginator
    {
        return Post::where('user_id', $userId)
            ->drafts()
            ->latest()
            ->paginate(20);
    }
    
    public function getPostQuotes(int $postId): LengthAwarePaginator
    {
        return Post::where('quoted_post_id', $postId)
            ->with('user:id,name,username,avatar')
            ->withCount('likes', 'comments')
            ->latest()
            ->paginate(20);
    }
    
    private function getFollowingIds(int $userId): array
    {
        return \DB::table('follows')
            ->where('follower_id', $userId)
            ->pluck('following_id')
            ->push($userId) // Include user's own posts
            ->toArray();
    }
}