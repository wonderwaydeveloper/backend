<?php

namespace App\Contracts\Services;

use App\DTOs\PostDTO;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostServiceInterface
{
    public function getPublicPosts(int $page = 1): LengthAwarePaginator;
    public function createPost(PostDTO $postDTO, ?UploadedFile $image = null, ?UploadedFile $video = null): Post;
    public function getPostWithRelations(Post $post): array;
    public function deletePost(Post $post): void;
    public function toggleLike(Post $post, User $user): array;
    public function getUserTimeline(User $user, int $limit = 20): array;
    public function getUserDrafts(User $user): LengthAwarePaginator;
    public function publishPost(Post $post): Post;
    public function createQuotePost(array $data, User $user, Post $originalPost): Post;
    public function getPostQuotes(Post $post): LengthAwarePaginator;
    public function updatePost(Post $post, array $data): Post;
    public function getEditHistory(Post $post): array;
}