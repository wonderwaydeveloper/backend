<?php

namespace App\Services;

use App\Models\{Space, User};
use App\Contracts\Repositories\SpaceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SpaceService
{
    public function __construct(
        private SpaceRepositoryInterface $spaceRepository,
        private SpaceParticipantService $participantService
    ) {}

    public function createSpace(User $host, array $data): Space
    {
        return DB::transaction(function () use ($host, $data) {
            $space = $this->spaceRepository->create([
                'host_id' => $host->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'privacy' => $data['privacy'] ?? 'public',
                'max_participants' => $data['max_participants'] ?? 10,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'status' => isset($data['scheduled_at']) ? 'scheduled' : 'live',
                'started_at' => isset($data['scheduled_at']) ? null : now(),
            ]);

            $this->participantService->addParticipant($space, $host, 'host');

            return $space->fresh(['host']);
        });
    }

    public function joinSpace(Space $space, User $user): array
    {
        if (!$this->canJoin($space, $user)) {
            throw new \Exception('Cannot join this space');
        }

        if ($space->current_participants >= $space->max_participants) {
            throw new \Exception('Space is full');
        }

        return DB::transaction(function () use ($space, $user) {
            $participant = $this->participantService->joinSpace($space, $user);
            
            if ($participant->wasRecentlyCreated) {
                $space->increment('current_participants');
            }

            broadcast(new \App\Events\SpaceParticipantJoined($space, $user));

            return ['success' => true, 'participant' => $participant];
        });
    }

    public function leaveSpace(Space $space, User $user): void
    {
        DB::transaction(function () use ($space, $user) {
            $participant = $this->participantService->leaveSpace($space, $user);
            
            if ($participant && $participant->wasChanged('status')) {
                $space->decrement('current_participants');
            }

            broadcast(new \App\Events\SpaceParticipantLeft($space, $user));
        });
    }

    public function endSpace(Space $space): void
    {
        DB::transaction(function () use ($space) {
            $space->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

            broadcast(new \App\Events\SpaceEnded($space));
        });
    }

    private function canJoin(Space $space, User $user): bool
    {
        if (!$space->isLive()) {
            return false;
        }

        if ($space->host_id === $user->id) {
            return true;
        }

        // Block/Mute check
        if ($space->host->hasBlocked($user->id) || $user->hasBlocked($space->host_id)) {
            return false;
        }

        if ($space->privacy === 'public') {
            return true;
        }

        if ($space->privacy === 'followers') {
            return $space->host->followers()->where('follower_id', $user->id)->exists();
        }

        if ($space->privacy === 'invited') {
            return $space->participants()->where('user_id', $user->id)->where('status', config('status.space_participant.invited'))->exists();
        }

        return false;
    }
}
