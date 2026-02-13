<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CommentRepositoryInterface;
use App\Models\Comment;
use App\Models\Post;

class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function getByPost(Post $post, int $perPage = 20)
    {
        return $post->comments()
            ->with('user:id,name,username,avatar')
            ->withCount('likes')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Comment
    {
        return Comment::create($data);
    }

    public function delete(Comment $comment): bool
    {
        return $comment->delete();
    }

    public function toggleLike(Comment $comment, int $userId): array
    {
        $existingLike = $comment->likes()->where('user_id', $userId)->first();

        if ($existingLike) {
            $existingLike->delete();
            return ['liked' => false];
        }

        $comment->likes()->create(['user_id' => $userId]);
        return ['liked' => true];
    }
}
