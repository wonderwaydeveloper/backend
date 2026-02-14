<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\MentionNotification;
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function __construct(
        private SpamDetectionService $spamDetection,
        private NotificationService $notificationService
    ) {}

    public function createComment(Post $post, User $user, string $content): Comment
    {
        // Check if post is draft
        if ($post->is_draft) {
            throw new \Exception('Cannot comment on draft posts');
        }

        // Check if user is blocked or muted
        if ($post->user && ($post->user->hasBlocked($user->id) || $post->user->hasMuted($user->id))) {
            throw new \Exception('You cannot comment on this post');
        }

        // Sanitize content
        $content = strip_tags($content);

        DB::beginTransaction();
        try {
            // Create comment
            $comment = $post->comments()->create([
                'user_id' => $user->id,
                'content' => $content,
            ]);

            // Increment post comments count
            $post->increment('comments_count');

            // Check spam
            $spamResult = $this->spamDetection->checkComment($comment);
            if ($spamResult['is_spam'] && $spamResult['score'] >= 80) {
                DB::rollBack();
                throw new \Exception('Comment detected as spam');
            }

            // Process mentions
            $mentionedUsers = $comment->processMentions($content);
            foreach ($mentionedUsers as $mentionedUser) {
                $mentionedUser->notify(new MentionNotification($user, $comment));
            }

            DB::commit();

            return $comment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteComment(Comment $comment): void
    {
        DB::beginTransaction();
        try {
            if ($comment->post->comments_count > 0) {
                $comment->post->decrement('comments_count');
            }
            $comment->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function toggleLike(Comment $comment, User $user): array
    {
        DB::beginTransaction();
        try {
            $existingLike = $comment->likes()->where('user_id', $user->id)->first();

            if ($existingLike) {
                $existingLike->delete();
                if ($comment->likes_count > 0) {
                    $comment->decrement('likes_count');
                }
                $liked = false;
            } else {
                $comment->likes()->create(['user_id' => $user->id]);
                $comment->increment('likes_count');
                $liked = true;
            }

            DB::commit();

            return [
                'liked' => $liked,
                'likes_count' => $comment->fresh()->likes_count
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
