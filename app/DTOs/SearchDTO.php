<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class SearchDTO
{
    public function __construct(
        public readonly string $query,
        public readonly ?string $type = null,
        public readonly ?array $filters = null,
        public readonly ?string $sortBy = null,
        public readonly ?string $sortOrder = 'desc',
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly ?int $userId = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            query: $request->query,
            type: $request->type,
            filters: $request->filters,
            sortBy: $request->sort_by,
            sortOrder: $request->sort_order ?? 'desc',
            page: $request->page ?? 1,
            perPage: min($request->per_page ?? 20, 50),
            userId: auth()->id()
        );
    }

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'type' => $this->type,
            'filters' => $this->filters,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'user_id' => $this->userId,
        ];
    }
}