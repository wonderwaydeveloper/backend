<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewFollowerNotification;
use App\Notifications\NewLikeNotification;
use App\Notifications\NewCommentNotification;
use App\Notifications\NewMessageNotification;
use App\Notifications\FollowRequestNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * ارسال نوتیفیکیشن برای فالو جدید
     */
    public function sendNewFollowerNotification(User $user, User $follower): void
    {
        $user->notify(new NewFollowerNotification($follower));
    }

    /**
     * ارسال نوتیفیکیشن برای لایک جدید
     */
    public function sendNewLikeNotification(User $user, User $liker, $likeable): void
    {
        $user->notify(new NewLikeNotification($liker, $likeable));
    }

    /**
     * ارسال نوتیفیکیشن برای کامنت جدید
     */
    public function sendNewCommentNotification(User $user, User $commenter, $commentable): void
    {
        $user->notify(new NewCommentNotification($commenter, $commentable));
    }

    /**
     * ارسال نوتیفیکیشن برای پیام جدید
     */
    public function sendNewMessageNotification(User $user, User $sender, $conversation): void
    {
        $user->notify(new NewMessageNotification($sender, $conversation));
    }

    /**
     * ارسال نوتیفیکیشن برای درخواست فالو
     */
    public function sendFollowRequestNotification(User $user, User $requester): void
    {
        $user->notify(new FollowRequestNotification($requester));
    }

    /**
     * مارک کردن همه نوتیفیکیشن‌ها به عنوان خوانده شده
     */
    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }

    /**
     * حذف همه نوتیفیکیشن‌ها
     */
    public function deleteAllNotifications(User $user): void
    {
        $user->notifications()->delete();
    }

    /**
     * دریافت نوتیفیکیشن‌های کاربر
     */
    public function getUserNotifications(User $user, array $filters = [])
    {
        $query = $user->notifications();

        if (isset($filters['unread_only']) && $filters['unread_only']) {
            $query->whereNull('read_at');
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }
}