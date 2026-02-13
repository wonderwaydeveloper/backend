<?php

namespace App\Listeners;

use App\Events\ContentIndexed;
use App\Services\SearchService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateSearchIndex implements ShouldQueue
{
    private $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function handle(ContentIndexed $event): void
    {
        if ($event->type === 'post') {
            $this->searchService->indexPost($event->model);
        } elseif ($event->type === 'user') {
            $this->searchService->indexUser($event->model);
        }
    }
}
