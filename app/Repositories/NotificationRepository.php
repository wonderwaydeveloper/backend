<?php

namespace App\Repositories;

use App\Contracts\NotificationRepositoryInterface;
use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }
    
    public function findById(int $id): ?Notification
    {
        return Notification::find($id);
    }
    
    public function getUserNotifications(int $userId): LengthAwarePaginator
    {
        return Notification::where('user_id', $userId)
            ->latest()
            ->paginate(20);
    }
    
    public function getUnreadNotifications(int $userId): Collection
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->latest()
            ->get();
    }
    
    public function markAsRead(int $notificationId): bool
    {
        return Notification::where('id', $notificationId)
            ->update(['read_at' => now()]);
    }
    
    public function markAllAsRead(int $userId): bool
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
    
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}