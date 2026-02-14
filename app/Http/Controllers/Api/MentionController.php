<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MentionRequest;
use App\Http\Resources\MentionResource;
use App\Models\Mention;
use App\Services\MentionService;
use Illuminate\Http\Request;

class MentionController extends Controller
{
    public function __construct(
        private MentionService $mentionService
    ) {}

    /**
     * Search users for mentions
     */
    public function searchUsers(MentionRequest $request)
    {
        $this->authorize('viewAny', Mention::class);

        $users = $this->mentionService->searchUsers(
            $request->validated()['q']
        );

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Get user's mentions
     */
    public function getUserMentions(Request $request)
    {
        $mentions = $this->mentionService->getUserMentions(auth()->user());

        return response()->json([
            'success' => true,
            'data' => MentionResource::collection($mentions),
            'meta' => [
                'current_page' => $mentions->currentPage(),
                'total' => $mentions->total(),
                'per_page' => $mentions->perPage(),
            ],
        ]);
    }

    /**
     * Get mentions for a specific post or comment
     */
    public function getMentions(Request $request, $type, $id)
    {
        $mentions = $this->mentionService->getMentionsForContent($type, $id);

        return response()->json([
            'success' => true,
            'data' => MentionResource::collection($mentions),
        ]);
    }
}
