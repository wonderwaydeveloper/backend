<?php

namespace App\Jobs;

use App\Services\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $model;
    public $type;

    public function __construct($model, $type)
    {
        $this->model = $model;
        $this->type = $type;
    }

    public function handle(SearchService $searchService): void
    {
        if ($this->type === 'post') {
            $searchService->indexPost($this->model);
        } elseif ($this->type === 'user') {
            $searchService->indexUser($this->model);
        }
    }
}
