<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function posts(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $results = $this->searchService->searchPosts(
            $request->q,
            $request->page ?? 1
        );

        return response()->json($results);
    }

    public function users(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:50',
            'page' => 'nullable|integer|min:1',
        ]);

        $results = $this->searchService->searchUsers(
            $request->q,
            $request->page ?? 1
        );

        return response()->json($results);
    }

    public function all(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $posts = $this->searchService->searchPosts($request->q, 1, 10);
        $users = $this->searchService->searchUsers($request->q, 1, 5);
        $hashtags = $this->searchService->searchHashtags($request->q, 1, 5);

        return response()->json([
            'posts' => $posts,
            'users' => $users,
            'hashtags' => $hashtags,
        ]);
    }
}
