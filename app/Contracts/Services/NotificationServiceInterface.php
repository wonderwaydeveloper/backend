<?php

namespace App\Contracts\Services;

use App\DTOs\NotificationDTO;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotificationServiceInterface
{
    public function send(NotificationDTO $dto): Notification;
    
    public function sendToUser(User $user, string $type, array $data): Notification;
    
    public function sendToFollowers(User $user, string $type, array $data): int;
    
    public function markAsRead(int $notificationId, int $userId): bool;
    
    public function markAllAsRead(int $userId): int;
    
    public function getUserNotifications(int $userId): LengthAwarePaginator;
    
    public function getUnreadCount(int $userId): int;
    
    public function deleteNotification(int $notificationId, int $userId): bool;
    
    public function updatePreferences(int $userId, array $preferences): bool;
}