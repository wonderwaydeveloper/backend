<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\ElasticsearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private ElasticsearchService $elasticsearch
    ) {}

    public function posts(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'user_id' => 'sometimes|integer',
            'has_media' => 'sometimes|boolean',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'limit' => 'sometimes|integer|min:1|max:50'
        ]);

        $results = $this->elasticsearch->searchPosts(
            $request->input('q'),
            $request->only(['user_id', 'has_media', 'date_from', 'limit'])
        );

        return response()->json([
            'data' => $results,
            'meta' => [
                'count' => $results->count()
            ]
        ]);
    }

    public function users(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'limit' => 'sometimes|integer|min:1|max:50'
        ]);

        $results = $this->elasticsearch->searchUsers(
            $request->input('q'),
            $request->only(['limit'])
        );

        return response()->json([
            'data' => $results,
            'meta' => [
                'count' => $results->count()
            ]
        ]);
    }
}