<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\TimelineService;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function __construct(
        private TimelineService $timelineService
    ) {}

    public function liveTimeline(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $result = $this->timelineService->getLiveTimeline($request->user(), $perPage);

        return response()->json($result);
    }

    public function getPostUpdates(Request $request, Post $post)
    {
        $result = $this->timelineService->getPostUpdates($post, $request->user());

        return response()->json($result);
    }
}
