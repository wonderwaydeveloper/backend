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

    public function createComment(Post $post, User $user, string $content, $mediaFile = null, ?int $parentId = null): Comment
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

        // Validate parent comment if replying
        if ($parentId) {
            $parentComment = Comment::find($parentId);
            if (!$parentComment || $parentComment->post_id !== $post->id) {
                throw new \Exception('Invalid parent comment');
            }
        }

        // Sanitize content
        $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
        $content = strip_tags($content);
        $content = trim($content);
        
        // Validate content length
        if (empty($content)) {
            throw new \Exception('Content cannot be empty');
        }
        if (strlen($content) > config('content.validation.content.comment.max_length')) {
            throw new \Exception('Content too long');
        }

        // NEW: Check spam BEFORE database save
        $spamCheck = $this->spamDetection->checkContent($content);
        if ($spamCheck['is_spam'] && $spamCheck['score'] >= 80) {
            throw new \Exception('Content detected as spam');
        }

        DB::beginTransaction();
        try {
            // Create comment
            $comment = new Comment();
            $comment->user_id = $user->id;
            $comment->post_id = $post->id;
            $comment->parent_id = $parentId;
            $comment->content = $content;
            $comment->save();
            
            // Increment parent replies_count if nested reply
            if ($parentId) {
                Comment::where('id', $parentId)->increment('replies_count');
            }
            
            // Handle media
            if ($mediaFile) {
                $media = app(\App\Services\MediaService::class)->uploadImage($mediaFile, $user);
                app(\App\Services\MediaService::class)->attachToModel($media, $comment);
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
            // Decrement parent replies_count if nested reply
            if ($comment->parent_id) {
                Comment::where('id', $comment->parent_id)->decrement('replies_count');
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

    public function updateComment(Comment $comment, User $user, string $content): Comment
    {
        if ($comment->user_id !== $user->id) {
            throw new \Exception('Unauthorized');
        }

        $content = trim(strip_tags($content));
        if (empty($content)) {
            throw new \Exception('Content cannot be empty');
        }

        DB::beginTransaction();
        try {
            $comment->content = $content;
            $comment->markAsEdited();
            DB::commit();
            return $comment->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function pinComment(Comment $comment, User $user): void
    {
        $post = $comment->post;
        if ($post->user_id !== $user->id) {
            throw new \Exception('Only post owner can pin comments');
        }

        DB::beginTransaction();
        try {
            Comment::where('post_id', $post->id)->update(['is_pinned' => false]);
            $comment->update(['is_pinned' => true]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function hideComment(Comment $comment, User $user): void
    {
        $post = $comment->post;
        if ($post->user_id !== $user->id && $comment->user_id !== $user->id) {
            throw new \Exception('Unauthorized');
        }

        $comment->update(['is_hidden' => true]);
    }
}
