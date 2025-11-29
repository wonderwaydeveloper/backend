<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\CommentMedia;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommentService
{
    /**
     * ایجاد کامنت جدید
     */
    public function createComment(User $user, Model $commentable, array $data): Comment
    {
        return DB::transaction(function () use ($user, $commentable, $data) {
            $comment = Comment::create([
                'user_id' => $user->id,
                'commentable_id' => $commentable->id,
                'commentable_type' => get_class($commentable),
                'content' => $data['content'],
                'parent_id' => $data['parent_id'] ?? null,
            ]);

            // آپلود مدیا
            if (isset($data['media'])) {
                $this->uploadMedia($comment, $data['media']);
            }

            // آپدیت تعداد کامنت‌ها
            $commentable->increment('comment_count');

            // اگر پاسخ به کامنت دیگری است
            if (isset($data['parent_id'])) {
                $parentComment = Comment::find($data['parent_id']);
                $parentComment->increment('reply_count');
            }

            return $comment->load('user', 'media');
        });
    }

    /**
     * آپدیت کامنت
     */
    public function updateComment(Comment $comment, array $data): Comment
    {
        $comment->update([
            'content' => $data['content'],
            'is_edited' => true,
        ]);

        return $comment->load('user', 'media');
    }

    /**
     * حذف کامنت
     */
    public function deleteComment(Comment $comment): void
    {
        DB::transaction(function () use ($comment) {
            // حذف مدیا
            foreach ($comment->media as $media) {
                Storage::disk('public')->delete($media->file_path);
                $media->delete();
            }

            // آپدیت تعداد کامنت‌های محتوای اصلی
            $comment->commentable->decrement('comment_count');

            // اگر پاسخ به کامنت دیگری است
            if ($comment->parent_id) {
                $parentComment = Comment::find($comment->parent_id);
                $parentComment->decrement('reply_count');
            }

            $comment->delete();
        });
    }

    /**
     * دریافت کامنت‌های یک محتوا
     */
    public function getComments(Model $commentable, array $filters = []): LengthAwarePaginator
    {
        $query = Comment::with(['user', 'media', 'replies.user'])
            ->where('commentable_id', $commentable->id)
            ->where('commentable_type', get_class($commentable))
            ->root()
            ->orderBy('created_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * ایجاد پاسخ به کامنت
     */
    public function createReply(User $user, Comment $parent, array $data): Comment
    {
        return DB::transaction(function () use ($user, $parent, $data) {
            $reply = Comment::create([
                'user_id' => $user->id,
                'commentable_id' => $parent->commentable_id,
                'commentable_type' => $parent->commentable_type,
                'content' => $data['content'],
                'parent_id' => $parent->id,
            ]);

            // آپدیت تعداد پاسخ‌ها
            $parent->increment('reply_count');
            $parent->commentable->increment('comment_count');

            return $reply->load('user', 'parent');
        });
    }

    /**
     * لایک/آنلایک کامنت
     */
    public function toggleLike(User $user, Comment $comment): bool
    {
        $like = $comment->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $comment->decrement('like_count');
            return false;
        } else {
            $comment->likes()->create(['user_id' => $user->id]);
            $comment->increment('like_count');
            return true;
        }
    }

    /**
     * دریافت مدل commentable
     */
    public function getCommentable(string $type, int $id): Model
    {
        $modelClass = $this->getModelClass($type);
        return $modelClass::findOrFail($id);
    }

    /**
     * تبدیل type به model class
     */
    private function getModelClass(string $type): string
    {
        return match($type) {
            'post' => \App\Models\Post::class,
            'article' => \App\Models\Article::class,
            default => throw new \Exception('Invalid commentable type'),
        };
    }

    /**
     * آپلود مدیا
     */
    private function uploadMedia(Comment $comment, $file): void
    {
        $uploadLimit = \App\Models\UploadLimit::getForType('comment');

        if ($file->getSize() > $uploadLimit->max_file_size * 1024) {
            throw new \Exception("File size exceeds limit");
        }

        if (!$uploadLimit->isMimeAllowed($file->getMimeType())) {
            throw new \Exception("File type not allowed");
        }

        $path = $file->store('comments/media', 'public');

        CommentMedia::create([
            'comment_id' => $comment->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'type' => 'image',
        ]);
    }
}