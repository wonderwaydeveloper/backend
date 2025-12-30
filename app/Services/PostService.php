<?php

namespace App\Services;

use App\Contracts\Services\{PostServiceInterface, FileUploadServiceInterface};
use App\Contracts\Repositories\PostRepositoryInterface;
use App\DTOs\{PostDTO, QuotePostDTO};
use App\Events\{PostInteraction, PostPublished};
use App\Jobs\ProcessPostJob;
use App\Models\{Post, User};
use App\Notifications\MentionNotification;
use App\Services\{SpamDetectionService, DatabaseOptimizationService, CacheOptimizationService, PostLikeService};
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Post Service Class
 *
 * Handles all post-related business logic including creation, updates,
 * likes, timeline management, and spam detection.
 *
 * @package App\Services
 * @author WonderWay Team
 * @version 1.0.0
 */
class PostService implements PostServiceInterface
{
    /**
     * PostService constructor.
     *
     * @param PostRepositoryInterface $postRepository Post repository for data access
     * @param SpamDetectionService $spamDetectionService Service for spam detection
     * @param DatabaseOptimizationService $databaseOptimizationService Service for database optimization
     * @param CacheOptimizationService $cacheService Service for cache optimization
     */
    public function __construct(
        private PostRepositoryInterface $postRepository,
        private SpamDetectionService $spamDetectionService,
        private CacheOptimizationService $cacheService,
        private FileUploadServiceInterface $fileUploadService,
        private PostLikeService $postLikeService
    ) {
    }

    /**
     * Get public posts with caching
     *
     * @param int $page Page number for pagination
     * @return LengthAwarePaginator Paginated posts
     */
    public function getPublicPosts(int $page = 1): LengthAwarePaginator
    {
        $cacheKey = "posts:public:page:{$page}";

        return cache()->remember($cacheKey, 600, function () use ($page) {
            return $this->postRepository->getPublicPosts($page);
        });
    }

    /**
     * Create a new post
     */
    public function createPost(PostDTO $postDTO, ?UploadedFile $image = null, ?UploadedFile $video = null): Post
    {
        $postData = $postDTO->toArray();
        $postData['content'] = $this->sanitizeContent($postData['content']);

        // Handle file uploads
        $postData = $this->handleFileUploads($postData, $image, $video);

        $post = $this->postRepository->create($postData);

        // Process post content and handle business logic
        $this->processPostBusinessLogic($post, $postDTO->isDraft, $video);

        return $post->load('user:id,name,username,avatar', 'hashtags');
    }

    /**
     * Get post with full relations
     */
    public function getPostWithRelations(Post $post): array
    {
        $post = $this->postRepository->findWithRelations($post->id, [
            'user:id,name,username,avatar',
            'comments.user:id,name,username,avatar',
            'hashtags',
            'quotedPost.user:id,name,username,avatar',
            'threadPosts.user:id,name,username,avatar',
        ])->loadCount('likes', 'comments', 'quotes');

        $response = $post->toArray();

        if ($post->threadPosts()->exists()) {
            $response['thread_info'] = [
                'total_posts' => $post->threadPosts->count() + 1,
                'is_main_thread' => true,
            ];
        }

        return $response;
    }

    /**
     * Delete post and cleanup
     */
    public function deletePost(Post $post): void
    {
        if ($post->image) {
            $this->fileUploadService->deleteFile($post->image);
        }

        $post->delete();
    }

    /**
     * Toggle like on post
     */
    public function toggleLike(Post $post, User $user): array
    {
        return $this->postLikeService->toggleLike($post, $user);
    }

    /**
     * Get user timeline
     */
    public function getUserTimeline(User $user, int $limit = 20): array
    {
        $cacheKey = "timeline:user:{$user->id}";
        
        $posts = cache()->remember($cacheKey, 300, function () use ($user, $limit) {
            // Simple optimized query
            return Post::with(['user:id,name,username,avatar'])
                ->published()
                ->latest()
                ->limit($limit)
                ->get();
        });

        return [
            'data' => $posts,
            'optimized' => true,
            'cached' => true,
        ];
    }

    /**
     * Get user drafts
     */
    public function getUserDrafts(User $user): LengthAwarePaginator
    {
        return $this->postRepository->getUserDrafts($user->id);
    }

    /**
     * Publish draft post
     */
    public function publishPost(Post $post): Post
    {
        $post->update([
            'is_draft' => false,
            'published_at' => now(),
        ]);

        return $post;
    }

    /**
     * Create quote post
     */
    public function createQuotePost(QuotePostDTO $quoteDTO): Post
    {
        $quotePost = Post::create($quoteDTO->toArray());

        $this->processPostContent($quotePost);

        broadcast(new PostPublished(
            $quotePost->load('user:id,name,username,avatar', 'quotedPost.user:id,name,username,avatar')
        ));

        return $quotePost->load('user:id,name,username,avatar', 'quotedPost.user:id,name,username,avatar', 'hashtags');
    }

    /**
     * Get post quotes
     */
    public function getPostQuotes(Post $post): LengthAwarePaginator
    {
        return $this->postRepository->getPostQuotes($post->id);
    }

    /**
     * Update post content
     */
    public function updatePost(Post $post, array $data): Post
    {
        try {
            $post->editPost(
                $data['content'],
                $data['edit_reason'] ?? null
            );

            $post->syncHashtags();

            return $post->load('user:id,name,username,avatar', 'hashtags', 'edits');
        } catch (\Exception $e) {
            throw new BusinessLogicException($e->getMessage(), 'POST_UPDATE_FAILED');
        }
    }

    /**
     * Get post edit history
     */
    public function getEditHistory(Post $post): array
    {
        $edits = $post->edits()->with('post:id,content')->get();

        return [
            'current_content' => $post->content,
            'edit_history' => $edits,
        ];
    }

    /**
     * Process post content (hashtags and mentions)
     */
    private function processPostContent(Post $post): void
    {
        $post->syncHashtags();
        $mentionedUsers = $post->processMentions($post->content);

        foreach ($mentionedUsers as $mentionedUser) {
            $mentionedUser->notify(new MentionNotification(auth()->user(), $post));
        }
    }

    /**
     * Handle spam detection
     */
    private function handleSpamDetection(Post $post): void
    {
        $spamResult = $this->spamDetectionService->checkPost($post);

        if ($spamResult['is_spam']) {
            $post->delete();

            $errorType = 'SPAM_DETECTED';
            if (in_array('Too many links detected (3 links)', $spamResult['reasons'])) {
                $errorType = 'TOO_MANY_LINKS';
            }

            throw new BusinessLogicException('پست شما به دلیل مشکوک بودن تأیید نشد', $errorType);
        }
    }

    /**
     * Process post asynchronously
     */
    private function processPostAsync(Post $post, bool $isDraft): void
    {
        if (! $isDraft && ! app()->environment('testing')) {
            dispatch(new ProcessPostJob($post))->onQueue('high');
        }
    }

    /**
     * Handle file uploads
     */
    private function handleFileUploads(array $postData, ?UploadedFile $image, ?UploadedFile $video): array
    {
        if ($image) {
            $postData['image'] = $this->fileUploadService->uploadImage($image);
        }

        if ($video) {
            $postData['video'] = 'processing';
        }

        return $postData;
    }

    /**
     * Process post business logic
     */
    private function processPostBusinessLogic(Post $post, bool $isDraft, ?UploadedFile $video): void
    {
        if ($video) {
            $this->fileUploadService->uploadVideo($video, $post);
        }

        $this->processPostContent($post);

        if (!$isDraft) {
            $this->handleSpamDetection($post);
            $this->processPostAsync($post, $isDraft);
            broadcast(new PostPublished($post->load('user:id,name,username,avatar')));
        }
    }

    /**
     * Sanitize content to prevent XSS and other attacks
     */
    private function sanitizeContent(string $content): string
    {
        $content = strip_tags($content);
        $content = str_replace(chr(0), '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        return trim($content);
    }
}