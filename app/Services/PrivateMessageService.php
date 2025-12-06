<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\MessageMedia;
use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrivateMessageService
{
    /**
     * دریافت مکالمات کاربر
     */
    public function getUserConversations(User $user): LengthAwarePaginator
    {
        return $user->conversations()
            ->with(['lastMessage', 'activeUsers'])
            ->wherePivotNull('left_at')
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);
    }

    /**
     * ایجاد مکالمه جدید
     */
    public function createConversation(User $user, array $data): Conversation
    {
        return DB::transaction(function () use ($user, $data) {
            $userIds = $data['user_ids'];
            $userIds[] = $user->id; // اضافه کردن کاربر ایجاد کننده

            // بررسی مکالمه مستقیم موجود
            if ($data['type'] === 'direct' && count($userIds) === 2) {
                $existingConversation = $this->findDirectConversation($userIds[0], $userIds[1]);
                if ($existingConversation) {
                    return $existingConversation;
                }
            }

            $conversation = Conversation::create([
                'type' => $data['type'] ?? 'direct',
                'title' => $data['title'] ?? null,
                'created_by' => $user->id,
                'last_message_at' => now(),
            ]);

            // اضافه کردن کاربران به مکالمه
            foreach (array_unique($userIds) as $userId) {
                $role = $userId === $user->id ? 'admin' : 'member';
                $conversation->addParticipant(User::find($userId), $role);
            }

            return $conversation->load('activeUsers', 'lastMessage');
        });
    }

    /**
     * ارسال پیام
     */
    public function sendMessage(User $user, Conversation $conversation, array $data): PrivateMessage
    {
        return DB::transaction(function () use ($user, $conversation, $data) {
            $message = PrivateMessage::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'content' => $data['content'] ?? null,
                'type' => $data['type'] ?? 'text',
                'reply_to' => $data['reply_to'] ?? null,
            ]);

            // آپلود مدیا
            if (isset($data['media'])) {
                $this->uploadMedia($message, $data['media']);
            }

            // آپدیت زمان آخرین پیام
            $conversation->updateLastMessage();

            // بارگذاری روابط
            $message->load(['user', 'replyTo', 'media']);

            return $message;
        });
    }

    /**
     * دریافت پیام‌های مکالمه
     */


    public function getConversationMessages(Conversation $conversation, array $filters = []): LengthAwarePaginator
    {
        $query = $conversation->messages()
            ->with(['user', 'replyTo.user', 'media']) // اضافه کردن media
            ->visible()
            ->orderBy('created_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 50);
    }

    /**
     * علامت گذاری پیام‌ها به عنوان دیده شده
     */
    public function markMessagesAsSeen(User $user, Conversation $conversation): bool
    {
        return DB::transaction(function () use ($user, $conversation) {
            $unseenMessages = $conversation->messages()
                ->where('user_id', '!=', $user->id)
                ->unseen()
                ->get();

            foreach ($unseenMessages as $message) {
                $message->markAsSeen();
            }

            return true;
        });
    }

    /**
     * حذف پیام
     */
    public function deleteMessage(User $user, int $messageId): bool
    {
        return DB::transaction(function () use ($user, $messageId) {
            $message = PrivateMessage::findOrFail($messageId);

            // حذف مدیاها
            foreach ($message->media as $media) {
                Storage::disk('public')->delete($media->file_path);
                if ($media->thumbnail) {
                    Storage::disk('public')->delete($media->thumbnail);
                }
                $media->delete();
            }

            $message->softDelete();

            return true;
        });
    }

    /**
     * اضافه کردن کاربر به مکالمه
     */
    public function addParticipant(Conversation $conversation, int $userId): bool
    {
        if ($conversation->type !== 'group') {
            throw new \Exception('Only group conversations can have participants added');
        }

        $user = User::findOrFail($userId);
        $conversation->addParticipant($user);

        return true;
    }

    /**
     * ترک مکالمه
     */
    public function leaveConversation(User $user, Conversation $conversation): bool
    {
        if ($conversation->type === 'direct') {
            throw new \Exception('Cannot leave direct conversation');
        }

        $conversation->removeParticipant($user);

        return true;
    }

    /**
     * پیدا کردن مکالمه مستقیم بین دو کاربر
     */
    private function findDirectConversation(int $userId1, int $userId2): ?Conversation
    {
        return Conversation::where('type', 'direct')
            ->whereHas('users', function ($query) use ($userId1) {
                $query->where('user_id', $userId1);
            })
            ->whereHas('users', function ($query) use ($userId2) {
                $query->where('user_id', $userId2);
            })
            ->whereHas('users', function ($query) {
                $query->whereNull('left_at');
            }, '=', 2)
            ->first();
    }

    /**
     * آپلود مدیا برای پیام
     */
    private function uploadMedia(PrivateMessage $message, $file): void
    {
        $uploadLimit = \App\Models\UploadLimit::getForType('message');

        if ($file->getSize() > $uploadLimit->max_file_size * 1024) {
            throw new \Exception("File size exceeds limit");
        }

        if (!$uploadLimit->isMimeAllowed($file->getMimeType())) {
            throw new \Exception("File type not allowed");
        }

        // بررسی محدودیت‌های ویدیو
        if (str_starts_with($file->getMimeType(), 'video/')) {
            if (!$uploadLimit->is_video_allowed) {
                throw new \Exception("Video files not allowed");
            }

            // اینجا می‌توانید مدت زمان ویدیو را بررسی کنید
            // $duration = $this->getVideoDuration($file);
            // if (!$uploadLimit->isWithinVideoLimits($file->getSize(), $duration)) {
            //     throw new \Exception("Video exceeds size or duration limits");
            // }
        }

        $path = $file->store('messages/media', 'public');
        $type = $this->getMediaType($file->getMimeType());

        MessageMedia::create([
            'message_id' => $message->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'type' => $type,
        ]);
    }

    /**
     * تشخیص نوع مدیا
     */
    private function getMediaType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } else {
            return 'file';
        }
    }
}