<?php

namespace App\DTOs\Response;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchResponseDTO
{
    public function __construct(
        public readonly array $results,
        public readonly array $meta,
        public readonly ?array $suggestions = null,
        public readonly ?array $filters = null,
        public readonly string $type = 'all'
    ) {}

    public static function fromPaginator(LengthAwarePaginator $paginator, string $type = 'all'): self
    {
        return new self(
            results: $paginator->items(),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
            type: $type
        );
    }

    public static function fromMultipleResults(array $results): self
    {
        return new self(
            results: $results,
            meta: [
                'total_posts' => count($results['posts'] ?? []),
                'total_users' => count($results['users'] ?? []),
                'total_hashtags' => count($results['hashtags'] ?? []),
            ],
            type: 'all'
        );
    }

    public function withSuggestions(array $suggestions): self
    {
        return new self(
            results: $this->results,
            meta: $this->meta,
            suggestions: $suggestions,
            filters: $this->filters,
            type: $this->type
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'results' => $this->results,
            'meta' => $this->meta,
            'suggestions' => $this->suggestions,
            'filters' => $this->filters,
            'type' => $this->type,
        ], fn($value) => $value !== null);
    }
}