<?php

namespace App\DTOs\Response;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TimelineResponseDTO
{
    public function __construct(
        public readonly Collection $posts,
        public readonly array $meta,
        public readonly ?array $trending = null,
        public readonly ?array $suggestions = null
    ) {}

    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            posts: collect($paginator->items()),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ]
        );
    }

    public function withTrending(array $trending): self
    {
        return new self(
            posts: $this->posts,
            meta: $this->meta,
            trending: $trending,
            suggestions: $this->suggestions
        );
    }

    public function withSuggestions(array $suggestions): self
    {
        return new self(
            posts: $this->posts,
            meta: $this->meta,
            trending: $this->trending,
            suggestions: $suggestions
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'posts' => $this->posts->toArray(),
            'meta' => $this->meta,
            'trending' => $this->trending,
            'suggestions' => $this->suggestions,
        ], fn($value) => $value !== null);
    }
}