<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PollRequest;
use App\Http\Resources\PollResource;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    public function store(PollRequest $request)
    {
        $validated = $request->validated();
        
        $poll = DB::transaction(function () use ($validated) {
            $poll = Poll::create([
                'post_id' => $validated['post_id'],
                'question' => $validated['question'],
                'multiple_choice' => $validated['multiple_choice'] ?? false,
                'ends_at' => now()->addHours($validated['duration_hours']),
            ]);

            foreach ($validated['options'] as $optionText) {
                PollOption::create([
                    'poll_id' => $poll->id,
                    'text' => $optionText,
                ]);
            }
            
            return $poll;
        });

        return new PollResource($poll->load('options'));
    }

    public function vote(Poll $poll, PollOption $option)
    {
        if ($poll->isExpired()) {
            return response()->json(['error' => 'Poll has expired'], 400);
        }

        if ($option->poll_id !== $poll->id) {
            return response()->json(['error' => 'Invalid option for this poll'], 400);
        }

        $user = auth()->user();

        if ($poll->hasVoted($user->id)) {
            return response()->json(['error' => 'You have already voted'], 400);
        }

        DB::transaction(function () use ($poll, $option, $user) {
            // Create vote
            $poll->votes()->create([
                'poll_option_id' => $option->id,
                'user_id' => $user->id,
            ]);

            // Update counters
            $option->increment('votes_count');
            $poll->increment('total_votes');
        });

        return response()->json([
            'message' => 'Vote recorded successfully',
            'results' => $poll->results(),
            'total_votes' => $poll->fresh()->total_votes,
        ]);
    }

    public function results(Poll $poll)
    {
        return response()->json([
            'poll' => new PollResource($poll->load('options')),
            'results' => $poll->results(),
            'total_votes' => $poll->total_votes,
            'is_expired' => $poll->isExpired(),
            'user_voted' => $poll->hasVoted(auth()->id()),
        ]);
    }
}
