<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CommentRepositoryInterface;
use App\DTOs\CommentDTO;
use App\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function find(int $id): ?Comment
    {
        return Comment::find($id);
    }

    public function create(CommentDTO $dto): Comment
    {
        return Comment::create($dto->toArray());
    }

    public function update(int $id, CommentDTO $dto): Comment
    {
        $comment = Comment::findOrFail($id);
        $comment->update($dto->toArray());
        return $comment->fresh();
    }

    public function delete(int $id): bool
    {
        return Comment::destroy($id) > 0;
    }

    public function getPostComments(int $postId): LengthAwarePaginator
    {
        return Comment::where('post_id', $postId)
            ->whereNull('parent_id')
            ->with(['user:id,name,username,avatar', 'replies.user:id,name,username,avatar'])
            ->withCount('likes')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getUserComments(int $userId): LengthAwarePaginator
    {
        return Comment::where('user_id', $userId)
            ->with(['post:id,content', 'user:id,name,username,avatar'])
            ->withCount('likes')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getCommentReplies(int $commentId): Collection
    {
        return Comment::where('parent_id', $commentId)
            ->with(['user:id,name,username,avatar'])
            ->withCount('likes')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function like(int $commentId, int $userId): bool
    {
        $comment = Comment::findOrFail($commentId);
        
        if (!$comment->likes()->where('user_id', $userId)->exists()) {
            $comment->likes()->create(['user_id' => $userId]);
            $comment->increment('likes_count');
            return true;
        }
        
        return false;
    }

    public function unlike(int $commentId, int $userId): bool
    {
        $comment = Comment::findOrFail($commentId);
        
        if ($comment->likes()->where('user_id', $userId)->exists()) {
            $comment->likes()->where('user_id', $userId)->delete();
            $comment->decrement('likes_count');
            return true;
        }
        
        return false;
    }

    public function isLikedBy(int $commentId, int $userId): bool
    {
        return Comment::findOrFail($commentId)
            ->likes()
            ->where('user_id', $userId)
            ->exists();
    }
}