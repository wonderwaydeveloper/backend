<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\SpaceRepositoryInterface;
use App\Models\Space;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentSpaceRepository implements SpaceRepositoryInterface
{
    public function create(array $data): Space
    {
        return Space::create($data);
    }

    public function update(Space $space, array $data): Space
    {
        $space->update($data);
        return $space->fresh();
    }

    public function delete(Space $space): bool
    {
        return $space->delete();
    }

    public function findById(int $id): ?Space
    {
        return Space::with(['host:id,name,username,avatar', 'participants.user:id,name,username,avatar'])
            ->withCount('activeParticipants')
            ->find($id);
    }

    public function getLiveSpaces(int $perPage = 20): LengthAwarePaginator
    {
        return Space::live()
            ->public()
            ->with(['host:id,name,username,avatar'])
            ->withCount('activeParticipants')
            ->orderBy('current_participants', 'desc')
            ->paginate($perPage);
    }

    public function getPublicSpaces(int $perPage = 20): LengthAwarePaginator
    {
        return Space::public()
            ->with(['host:id,name,username,avatar'])
            ->withCount('activeParticipants')
            ->paginate($perPage);
    }

    public function getScheduledSpaces(int $perPage = 20): LengthAwarePaginator
    {
        return Space::where('status', 'scheduled')
            ->with(['host:id,name,username,avatar'])
            ->orderBy('scheduled_at', 'asc')
            ->paginate($perPage);
    }

    public function getSpacesByHost(int $hostId, int $perPage = 20): LengthAwarePaginator
    {
        return Space::where('host_id', $hostId)
            ->with(['host:id,name,username,avatar'])
            ->withCount('activeParticipants')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
