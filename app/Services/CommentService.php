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

    public function getPostComments(int $postId, int $perPage = 20)
    {
        $cacheKey = "post_comments_{$postId}_page_" . request('page', 1);
        return \Cache::remember($cacheKey, 300, function() use ($postId, $perPage) {
            return Comment::forPost($postId)->paginate($perPage);
        });
    }

    public function createComment(Post $post, User $user, string $content, $mediaFile = null): Comment
    {
        // Check if post is draft
        if ($post->is_draft) {
            throw new \Exception('Cannot comment on draft posts');
        }

        // Check reply settings
        if ($post->reply_settings === 'none' && $post->user_id !== $user->id) {
            throw new \Exception('Replies are disabled for this post');
        }

        // Check if user is blocked or muted
        if ($post->user && ($post->user->hasBlocked($user->id) || $post->user->hasMuted($user->id))) {
            throw new \Exception('You cannot comment on this post');
        }

        // Sanitize content - remove script tags and their content completely
        $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
        $content = strip_tags($content);
        $content = trim($content);
        
        // Validate content length
        if (empty($content)) {
            throw new \Exception('Content cannot be empty');
        }
        if (strlen($content) > config('validation.content.comment.max_length')) {
            throw new \Exception('Content too long');
        }

        DB::beginTransaction();
        try {
            // Create comment
            $comment = $post->comments()->create([
                'user_id' => $user->id,
                'content' => $content,
            ]);
            
            // Handle media
            if ($mediaFile) {
                $media = app(\App\Services\MediaService::class)->uploadImage($mediaFile, $user);
                app(\App\Services\MediaService::class)->attachToModel($media, $comment);
            }

            // Check spam AFTER creation but BEFORE commit
            $spamCheck = $this->spamDetection->checkComment($comment);
            if ($spamCheck['is_spam'] && $spamCheck['score'] >= 80) {
                DB::rollBack();
                throw new \Exception('Content detected as spam');
            }

            // Process mentions
            $mentionedUsers = $comment->processMentions($content);
            foreach ($mentionedUsers as $mentionedUser) {
                $mentionedUser->notify(new MentionNotification($user, $comment));
            }

            DB::commit();

            return $comment->load('media');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteComment(Comment $comment, User $user): void
    {
        // Authorization check
        if ($comment->user_id !== $user->id && !$user->hasPermissionTo('comment.delete.any')) {
            throw new \Exception('Unauthorized');
        }
        DB::beginTransaction();
        try {
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
