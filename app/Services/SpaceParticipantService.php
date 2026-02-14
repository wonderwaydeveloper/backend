<?php

namespace App\Services;

use App\Models\{Space, User, SpaceParticipant};
use App\Contracts\Repositories\SpaceParticipantRepositoryInterface;

class SpaceParticipantService
{
    public function __construct(
        private SpaceParticipantRepositoryInterface $participantRepository
    ) {}

    public function addParticipant(Space $space, User $user, string $role = 'listener'): SpaceParticipant
    {
        return $this->participantRepository->create([
            'space_id' => $space->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'joined',
            'joined_at' => now(),
        ]);
    }

    public function joinSpace(Space $space, User $user): SpaceParticipant
    {
        return $this->participantRepository->updateOrCreate(
            ['space_id' => $space->id, 'user_id' => $user->id],
            [
                'status' => 'joined',
                'joined_at' => now(),
                'left_at' => null,
            ]
        );
    }

    public function leaveSpace(Space $space, User $user): ?SpaceParticipant
    {
        $participant = $this->participantRepository->findBySpaceAndUser($space->id, $user->id);

        if (!$participant) {
            return null;
        }

        $participant->update([
            'status' => 'left',
            'left_at' => now(),
        ]);

        return $participant;
    }

    public function updateRole(SpaceParticipant $participant, string $role): SpaceParticipant
    {
        $participant->update(['role' => $role]);
        
        broadcast(new \App\Events\SpaceParticipantRoleChanged($participant->space, $participant));

        return $participant;
    }

    public function muteParticipant(SpaceParticipant $participant): void
    {
        $participant->update(['is_muted' => true]);
    }

    public function unmuteParticipant(SpaceParticipant $participant): void
    {
        $participant->update(['is_muted' => false]);
    }
}
