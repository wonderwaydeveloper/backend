<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\SpaceParticipantRepositoryInterface;
use App\Models\SpaceParticipant;

class EloquentSpaceParticipantRepository implements SpaceParticipantRepositoryInterface
{
    public function create(array $data): SpaceParticipant
    {
        return SpaceParticipant::create($data);
    }

    public function updateOrCreate(array $attributes, array $values): SpaceParticipant
    {
        return SpaceParticipant::updateOrCreate($attributes, $values);
    }

    public function findBySpaceAndUser(int $spaceId, int $userId): ?SpaceParticipant
    {
        return SpaceParticipant::where('space_id', $spaceId)
            ->where('user_id', $userId)
            ->first();
    }

    public function getParticipantsBySpace(int $spaceId)
    {
        return SpaceParticipant::where('space_id', $spaceId)
            ->with('user:id,name,username,avatar')
            ->get();
    }

    public function getActiveParticipants(int $spaceId)
    {
        return SpaceParticipant::where('space_id', $spaceId)
            ->where('status', 'joined')
            ->with('user:id,name,username,avatar')
            ->get();
    }
}
