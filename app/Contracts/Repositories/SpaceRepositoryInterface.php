<?php

namespace App\Contracts\Repositories;

use App\Models\Space;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SpaceRepositoryInterface
{
    public function create(array $data): Space;
    
    public function update(Space $space, array $data): Space;
    
    public function delete(Space $space): bool;
    
    public function findById(int $id): ?Space;
    
    public function getLiveSpaces(int $perPage = 20): LengthAwarePaginator;
    
    public function getPublicSpaces(int $perPage = 20): LengthAwarePaginator;
    
    public function getScheduledSpaces(int $perPage = 20): LengthAwarePaginator;
    
    public function getSpacesByHost(int $hostId, int $perPage = 20): LengthAwarePaginator;
}
