<?php

namespace App\Contracts\Repositories;

use App\DTOs\CommentDTO;
use App\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CommentRepositoryInterface
{
    public function find(int $id): ?Comment;
    
    public function create(CommentDTO $dto): Comment;
    
    public function update(int $id, CommentDTO $dto): Comment;
    
    public function delete(int $id): bool;
    
    public function getPostComments(int $postId): LengthAwarePaginator;
    
    public function getUserComments(int $userId): LengthAwarePaginator;
    
    public function getCommentReplies(int $commentId): Collection;
    
    public function like(int $commentId, int $userId): bool;
    
    public function unlike(int $commentId, int $userId): bool;
    
    public function isLikedBy(int $commentId, int $userId): bool;
}