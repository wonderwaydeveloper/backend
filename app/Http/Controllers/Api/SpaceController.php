<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SpaceRequest;
use App\Http\Resources\SpaceResource;
use App\Models\Space;
use App\Models\SpaceParticipant;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        $spaces = Space::live()
            ->public()
            ->with(['host:id,name,username,avatar', 'participants.user:id,name,username,avatar'])
            ->withCount('activeParticipants')
            ->orderBy('current_participants', 'desc')
            ->paginate(20);

        return SpaceResource::collection($spaces);
    }

    public function store(SpaceRequest $request)
    {
        $validated = $request->validated();

        $space = Space::create([
            'host_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? true,
            'max_participants' => $validated['max_participants'] ?? 10,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => isset($validated['scheduled_at']) ? 'scheduled' : 'live',
            'started_at' => isset($validated['scheduled_at']) ? null : now(),
        ]);

        // Add host as participant
        SpaceParticipant::create([
            'space_id' => $space->id,
            'user_id' => $request->user()->id,
            'role' => 'host',
            'status' => 'joined',
            'joined_at' => now(),
        ]);

        $space->increment('current_participants');
        $space->load('host');

        return new SpaceResource($space);
    }

    public function show(Space $space)
    {
        $space->load([
            'host:id,name,username,avatar',
            'participants.user:id,name,username,avatar',
        ])->loadCount('activeParticipants');

        return new SpaceResource($space);
    }

    public function join(Request $request, Space $space)
    {
        $userId = $request->user()->id;

        if (! $space->canJoin($userId)) {
            return response()->json(['message' => 'Cannot join this space'], 403);
        }

        if ($space->current_participants >= $space->max_participants) {
            return response()->json(['message' => 'Space is full'], 403);
        }

        $participant = SpaceParticipant::updateOrCreate(
            ['space_id' => $space->id, 'user_id' => $userId],
            [
                'status' => 'joined',
                'joined_at' => now(),
                'left_at' => null,
            ]
        );

        if ($participant->wasRecentlyCreated) {
            $space->increment('current_participants');
        }

        broadcast(new \App\Events\SpaceParticipantJoined($space, $request->user()));

        return response()->json(['message' => 'Joined space successfully']);
    }

    public function leave(Request $request, Space $space)
    {
        $participant = SpaceParticipant::where('space_id', $space->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $participant) {
            return response()->json(['message' => 'Not in this space'], 404);
        }

        $participant->update([
            'status' => 'left',
            'left_at' => now(),
        ]);

        $space->decrement('current_participants');

        broadcast(new \App\Events\SpaceParticipantLeft($space, $request->user()));

        return response()->json(['message' => 'Left space successfully']);
    }

    public function updateRole(Request $request, Space $space, SpaceParticipant $participant)
    {
        $this->authorize('update', $space);

        $request->validate([
            'role' => 'required|in:co_host,speaker,listener',
        ]);

        $participant->update(['role' => $request->role]);

        broadcast(new \App\Events\SpaceParticipantRoleChanged($space, $participant));

        return response()->json(['message' => 'Role updated successfully']);
    }

    public function end(Request $request, Space $space)
    {
        $this->authorize('update', $space);

        $space->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        broadcast(new \App\Events\SpaceEnded($space));

        return response()->json(['message' => 'Space ended successfully']);
    }
}
