<?php

namespace App\Services;

use App\Models\Moment;
use App\Models\Post;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MomentService
{
    public function __construct(
        private MediaService $mediaService
    ) {}
    public function createMoment(User $user, array $data): Moment
    {
        return DB::transaction(function () use ($user, $data) {
            $moment = Moment::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'privacy' => $data['privacy'] ?? 'public',
                'cover_image' => $data['cover_image'] ?? null,
            ]);

            if (isset($data['post_ids']) && is_array($data['post_ids'])) {
                foreach ($data['post_ids'] as $index => $postId) {
                    $moment->addPost($postId, $index);
                }
            }

            return $moment->load('creator', 'posts', 'media');
        });
    }

    public function updateMoment(Moment $moment, array $data): Moment
    {
        $moment->update($data);
        return $moment->fresh();
    }

    public function deleteMoment(Moment $moment): bool
    {
        return $moment->delete();
    }

    public function getPublicMoments(bool $featuredOnly = false): LengthAwarePaginator
    {
        $query = Moment::public()
            ->with(['user:id,name,username,avatar'])
            ->withCount('posts');

        if ($featuredOnly) {
            $query->featured();
        }

        return $query->latest()->paginate(20);
    }

    public function getUserMoments(User $user): LengthAwarePaginator
    {
        return $user->moments()
            ->withCount('posts')
            ->latest()
            ->paginate(20);
    }

    public function getMoment(Moment $moment, ?User $user = null): Moment
    {
        if ($moment->privacy === 'private' && (!$user || $moment->user_id !== $user->id)) {
            throw new \Exception('Moment not found');
        }

        $moment->load([
            'user:id,name,username,avatar',
            'posts.user:id,name,username,avatar',
            'posts.hashtags:id,name,slug',
        ])->loadCount('posts');

        $moment->incrementViews();

        return $moment;
    }

    public function addPostToMoment(Moment $moment, int $postId, ?int $position = null): void
    {
        if ($moment->posts()->where('post_id', $postId)->exists()) {
            throw new \Exception('Post already in moment');
        }

        $moment->addPost($postId, $position);
    }

    public function removePostFromMoment(Moment $moment, int $postId): void
    {
        if (!$moment->posts()->where('post_id', $postId)->exists()) {
            throw new \Exception('Post not in moment');
        }

        $moment->removePost($postId);
    }

    public function getFeaturedMoments(): Collection
    {
        return Moment::public()
            ->featured()
            ->with(['user:id,name,username,avatar'])
            ->withCount('posts')
            ->latest()
            ->limit(10)
            ->get();
    }
}
