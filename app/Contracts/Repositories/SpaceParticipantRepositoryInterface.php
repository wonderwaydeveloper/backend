<?php

namespace App\Contracts\Repositories;

use App\Models\SpaceParticipant;

interface SpaceParticipantRepositoryInterface
{
    public function create(array $data): SpaceParticipant;
    
    public function updateOrCreate(array $attributes, array $values): SpaceParticipant;
    
    public function findBySpaceAndUser(int $spaceId, int $userId): ?SpaceParticipant;
    
    public function getParticipantsBySpace(int $spaceId);
    
    public function getActiveParticipants(int $spaceId);
}
