<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchPostsRequest;
use App\Http\Requests\SearchUsersRequest;
use App\Http\Requests\SearchHashtagsRequest;
use App\Http\Resources\SearchResultResource;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function posts(SearchPostsRequest $request)
    {
        $this->authorize('search', auth()->user());
        
        $filters = $request->only([
            'user_id', 'has_media', 'date_from', 'date_to',
            'min_likes', 'hashtags', 'sort',
        ]);

        $results = $this->searchService->searchPosts(
            $request->q,
            $request->page ?? 1,
            20,
            $filters
        );

        return response()->json($results);
    }

    public function users(SearchUsersRequest $request)
    {
        $filters = $request->only([
            'verified', 'min_followers', 'location', 'sort',
        ]);

        $results = $this->searchService->searchUsers(
            $request->q,
            $request->page ?? 1,
            20,
            $filters
        );

        return response()->json($results);
    }

    public function hashtags(SearchHashtagsRequest $request)
    {
        $filters = $request->only(['min_posts', 'sort']);

        $results = $this->searchService->searchHashtags(
            $request->q,
            $request->page ?? 1,
            20,
            $filters
        );

        return response()->json($results);
    }

    public function all(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $results = $this->searchService->advancedSearch($request->q);

        return response()->json($results);
    }

    public function advanced(Request $request)
    {
        
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'type' => 'nullable|in:posts,users,hashtags',
            // Post filters
            'user_id' => 'nullable|integer|exists:users,id',
            'has_media' => 'nullable|boolean',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'min_likes' => 'nullable|integer|min:0',
            'hashtags' => 'nullable|array',
            // User filters
            'verified' => 'nullable|boolean',
            'min_followers' => 'nullable|integer|min:0',
            'location' => 'nullable|string|max:100',
            // General
            'sort' => 'nullable|string|max:20',
        ]);

        $filters = $request->except(['q']);

        $results = $this->searchService->advancedSearch($request->q, $filters);

        return response()->json($results);
    }

    public function suggestions(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:50',
            'type' => 'nullable|in:all,users,hashtags',
        ]);

        $suggestions = $this->searchService->getSuggestions(
            $request->q,
            $request->type ?? 'all'
        );

        return response()->json($suggestions);
    }
}
