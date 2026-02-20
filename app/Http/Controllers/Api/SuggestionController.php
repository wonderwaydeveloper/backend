<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserSuggestionService;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    protected $suggestionService;

    public function __construct(UserSuggestionService $suggestionService)
    {
        $this->suggestionService = $suggestionService;
    }

    public function users(Request $request)
    {
        $limit = $request->input('limit', 10);
        $suggestions = $this->suggestionService->getSuggestions($request->user()->id, $limit);

        return response()->json(['data' => $suggestions]);
    }
}
