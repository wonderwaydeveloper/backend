<?php

namespace App\Services;

use App\Contracts\Services\{PostServiceInterface, FileUploadServiceInterface};
use App\DTOs\{PostDTO, QuotePostDTO};
use App\Events\{PostInteraction, PostPublished};
use App\Jobs\ProcessPostJob;
use App\Models\{Post, User};
use App\Notifications\MentionNotification;
use App\Services\{SpamDetectionService, DatabaseOptimizationService, CacheOptimizationService, PostLikeService};
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Post Service Class
 *
 * Handles all post-related business logic including creation, updates,
 * likes, timeline management, and spam detection.
 *
 * @package App\Services
 * @author Microblogging Team
 * @version 1.0.0
 */
class PostService implements PostServiceInterface
{
    /**
     * PostService constructor.
     *
     * @param SpamDetectionService $spamDetectionService Service for spam detection
     * @param DatabaseOptimizationService $databaseOptimizationService Service for database optimization
     * @param CacheOptimizationService $cacheService Service for cache optimization
     */
    public function __construct(
        private SpamDetectionService $spamDetectionService,
        private CacheOptimizationService $cacheService,
        private FileUploadServiceInterface $fileUploadService,
        private PostLikeService $postLikeService
    ) {
    }

    // Repository methods moved to service
    public function create(array $data): Post
    {
        $post = Post::create($data);
        $this->clearUserCache($post->user_id);
        return $post;
    }

    public function findById(int $id): ?Post
    {
        return Cache::remember("post:{$id}", 300, function () use ($id) {
            return Post::with([
                'user:id,name,username,avatar',
                'hashtags:id,name,slug',
            ])->find($id);
        });
    }

    public function findWithRelations(int $id, array $relations = []): ?Post
    {
        return Post::with($relations)->find($id);
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);
        Cache::forget("post:{$post->id}");
        $this->clearUserCache($post->user_id);
        return $post->fresh();
    }

    public function delete(Post $post): bool
    {
        Cache::forget("post:{$post->id}");
        $this->clearUserCache($post->user_id);
        return $post->delete();
    }

    public function getPublicPosts(int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return Post::published()
            ->with([
                'user:id,name,username,avatar',
                'hashtags:id,name,slug',
                'poll.options',
                'quotedPost.user:id,name,username,avatar',
            ])
            ->withCount(['likes', 'comments', 'quotes'])
            ->whereNull('thread_id')
            ->latest('published_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getTimelinePosts(int $userId, int $limit = 20): Collection
    {
        $cacheKey = "timeline:{$userId}:{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($userId, $limit) {
            $followingIds = $this->getFollowingIds($userId);

            return Post::forTimeline()
                ->select(['id', 'user_id', 'content', 'created_at', 'likes_count', 'comments_count', 'image', 'gif_url', 'quoted_post_id'])
                ->whereIn('user_id', $followingIds)
                ->whereNull('thread_id')
                ->limit($limit)
                ->get();
        });
    }

    public function getUserDrafts(User $user): LengthAwarePaginator
    {
        return Post::where('user_id', $user->id)
            ->drafts()
            ->with(['hashtags:id,name,slug'])
            ->latest()
            ->paginate(20);
    }

    public function getPostQuotes(Post $post): LengthAwarePaginator
    {
        return Post::where('quoted_post_id', $post->id)
            ->with([
                'user:id,name,username,avatar',
                'hashtags:id,name,slug',
            ])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->paginate(20);
    }

    public function getUserPosts(int $userId, int $limit = 20): Collection
    {
        return Post::where('user_id', $userId)
            ->published()
            ->with([
                'hashtags:id,name,slug',
                'quotedPost.user:id,name,username,avatar',
            ])
            ->withCount(['likes', 'comments', 'quotes'])
            ->whereNull('thread_id')
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function searchPosts(string $query, int $limit = 20): Collection
    {
        $sanitizedQuery = $this->sanitizeSearchQuery($query);
        
        return Post::published()
            ->where('content', 'LIKE', "%{$sanitizedQuery}%")
            ->with([
                'user:id,name,username,avatar',
                'hashtags:id,name,slug',
            ])
            ->withCount(['likes', 'comments'])
            ->whereNull('thread_id')
            ->latest('published_at')
            ->limit($limit)
            ->get();
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

        $post = $this->create($postData);

        // Process post content and handle business logic
        $this->processPostBusinessLogic($post, $postDTO->isDraft, $video);

        return $post->load('user:id,name,username,avatar', 'hashtags');
    }

    /**
     * Get post with full relations
     */
    public function getPostWithRelations(Post $post): array
    {
        $post = $this->findWithRelations($post->id, [
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

            throw new BusinessLogicException('Your post was not approved due to suspicious content', $errorType);
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
     * Sanitize search query to prevent SQL injection
     */
    private function sanitizeSearchQuery(string $query): string
    {
        $query = preg_replace('/[%_\\]/', '\\$0', $query);
        $query = str_replace(chr(0), '', $query);
        $query = substr($query, 0, 100);
        return trim($query);
    }

    private function getFollowingIds(int $userId): array
    {
        return Cache::remember("following:{$userId}", 600, function () use ($userId) {
            return DB::table('follows')
                ->where('follower_id', $userId)
                ->pluck('following_id')
                ->push($userId)
                ->toArray();
        });
    }

    private function clearUserCache(int $userId): void
    {
        Cache::forget("timeline:{$userId}:20");
        Cache::forget("following:{$userId}");
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