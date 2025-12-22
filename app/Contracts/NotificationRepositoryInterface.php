<?php

namespace App\Contracts;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    public function create(array $data): Notification;
    
    public function findById(int $id): ?Notification;
    
    public function getUserNotifications(int $userId): LengthAwarePaginator;
    
    public function getUnreadNotifications(int $userId): Collection;
    
    public function markAsRead(int $notificationId): bool;
    
    public function markAllAsRead(int $userId): bool;
    
    public function getUnreadCount(int $userId): int;
}