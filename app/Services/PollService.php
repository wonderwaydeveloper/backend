<?php

namespace App\Services;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PollService
{
    public function createPoll(array $data): Poll
    {
        return DB::transaction(function () use ($data) {
            $poll = Poll::create([
                'post_id' => $data['post_id'],
                'question' => $data['question'],
                'multiple_choice' => $data['multiple_choice'] ?? false,
                'ends_at' => now()->addHours($data['duration_hours']),
            ]);

            foreach ($data['options'] as $optionText) {
                PollOption::create([
                    'poll_id' => $poll->id,
                    'text' => $optionText,
                ]);
            }

            return $poll->load('options');
        });
    }

    public function vote(Poll $poll, PollOption $option, User $user): array
    {
        // Check if poll expired
        if ($poll->isExpired()) {
            throw new \Exception('Poll has expired');
        }

        // Check if option belongs to poll
        if ($option->poll_id !== $poll->id) {
            throw new \Exception('Invalid option for this poll');
        }

        // Check if user already voted
        if ($poll->hasVoted($user->id)) {
            throw new \Exception('You have already voted');
        }

        // Check Block/Mute
        $pollOwner = $poll->post->user;
        if ($pollOwner->hasBlocked($user->id)) {
            throw new \Exception('You cannot vote on this poll');
        }

        if ($user->hasBlocked($pollOwner->id)) {
            throw new \Exception('You cannot vote on this poll');
        }

        return DB::transaction(function () use ($poll, $option, $user) {
            // Create vote
            $poll->votes()->create([
                'poll_option_id' => $option->id,
                'user_id' => $user->id,
            ]);

            // Update counters
            $option->increment('votes_count');
            $poll->increment('total_votes');

            // Broadcast event
            event(new \App\Events\PollVoted($poll, $user));

            return [
                'message' => 'Vote recorded successfully',
                'results' => $poll->fresh()->results(),
                'total_votes' => $poll->fresh()->total_votes,
            ];
        });
    }

    public function getResults(Poll $poll, User $user): array
    {
        return [
            'poll' => $poll->load('options'),
            'results' => $poll->results(),
            'total_votes' => $poll->total_votes,
            'is_expired' => $poll->isExpired(),
            'user_voted' => $poll->hasVoted($user->id),
        ];
    }

    public function deletePoll(Poll $poll): bool
    {
        return DB::transaction(function () use ($poll) {
            return $poll->delete();
        });
    }
}
