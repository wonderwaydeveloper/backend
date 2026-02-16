<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PollRequest;
use App\Http\Resources\PollResource;
use App\Models\Poll;
use App\Models\PollOption;
use App\Services\PollService;
use Symfony\Component\HttpFoundation\Response;

class PollController extends Controller
{
    public function __construct(
        private PollService $pollService
    ) {}

    public function store(PollRequest $request)
    {
        $this->authorize('create', Poll::class);
        
        $poll = $this->pollService->createPoll($request->validated());

        return new PollResource($poll);
    }

    public function vote(Poll $poll, PollOption $option)
    {
        $this->authorize('vote', $poll);
        
        try {
            $result = $this->pollService->vote($poll, $option, auth()->user());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function results(Poll $poll)
    {
        $result = $this->pollService->getResults($poll, auth()->user());
        return response()->json($result);
    }

    public function destroy(Poll $poll)
    {
        $this->authorize('delete', $poll);
        
        $this->pollService->deletePoll($poll);
        
        return response()->json(['message' => 'Poll deleted successfully']);
    }
}
