<?php

namespace App\Contracts\Repositories;

use App\Models\Comment;
use App\Models\Post;

interface CommentRepositoryInterface
{
    public function getByPost(Post $post, int $perPage = 20);
    public function create(array $data): Comment;
    public function delete(Comment $comment): bool;
    public function toggleLike(Comment $comment, int $userId): array;
}
