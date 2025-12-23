<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\HashtagRepositoryInterface;
use App\DTOs\HashtagDTO;
use App\Models\Hashtag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentHashtagRepository implements HashtagRepositoryInterface
{
    public function find(int $id): ?Hashtag
    {
        return Hashtag::find($id);
    }

    public function findByName(string $name): ?Hashtag
    {
        return Hashtag::where('name', $name)->first();
    }

    public function create(HashtagDTO $dto): Hashtag
    {
        return Hashtag::create($dto->toArray());
    }

    public function update(int $id, HashtagDTO $dto): Hashtag
    {
        $hashtag = Hashtag::findOrFail($id);
        $hashtag->update($dto->toArray());
        return $hashtag->fresh();
    }

    public function delete(int $id): bool
    {
        return Hashtag::destroy($id) > 0;
    }

    public function getTrending(int $limit = 10): Collection
    {
        return Hashtag::where('is_trending', true)
            ->orderBy('posts_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function search(string $query): LengthAwarePaginator
    {
        return Hashtag::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->orderBy('posts_count', 'desc')
            ->paginate(20);
    }

    public function getHashtagPosts(int $hashtagId): LengthAwarePaginator
    {
        return Hashtag::findOrFail($hashtagId)
            ->posts()
            ->with(['user:id,name,username,avatar'])
            ->withCount(['likes', 'comments'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function incrementUsage(int $hashtagId): bool
    {
        return Hashtag::where('id', $hashtagId)->increment('posts_count') > 0;
    }

    public function getPopular(int $limit = 20): Collection
    {
        return Hashtag::orderBy('posts_count', 'desc')
            ->limit($limit)
            ->get();
    }
}