<?php

namespace App\Http\Controllers\Api;

use App\DTOs\{PostDTO, QuotePostDTO};
use App\Http\Controllers\Controller;
use App\Http\Requests\{StorePostRequest, UpdatePostRequest};
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use App\Rules\ContentLength;
use Illuminate\Http\{JsonResponse, Request};
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $posts = $this->postService->getPublicPosts(request('page', 1));
            return response()->json([
                'data' => PostResource::collection($posts->items()),
                'meta' => ['current_page' => $posts->currentPage(), 'total' => $posts->total()]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch posts'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);
        
        try {
            $dto = PostDTO::fromRequest($request->validated(), $request->user()->id);
            $post = $this->postService->createPost($dto, []);
            return response()->json(new PostResource($post), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create post'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Post $post): JsonResponse
    {
        $this->authorize('view', $post);
        
        try {
            // Track view count and impression (Twitter standard)
            $post->increment('views_count');
            $post->increment('impression_count');
            
            // Calculate engagement rate
            $totalEngagements = $post->likes_count + $post->comments_count + $post->reposts_count;
            if ($post->impression_count > 0) {
                $post->engagement_rate = round(($totalEngagements / $post->impression_count) * 100, 2);
                $post->save();
            }
            
            // Track analytics event
            if (auth()->id() && $post->id) {
                \App\Models\AnalyticsEvent::track(
                    'post_view',
                    'post',
                    $post->id,
                    auth()->id()
                );
            }
            
            return response()->json(new PostResource($post->load(['user', 'likes', 'comments'])));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);
        $updatedPost = $this->postService->updatePost($post, $request->validated());
        return response()->json(new PostResource($updatedPost));
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);
        $this->postService->deletePost($post);
        return response()->json(['message' => 'Post deleted successfully']);
    }

    public function like(Post $post): JsonResponse
    {
        $result = $this->postService->toggleLike($post, auth()->user());
        
        // Track analytics event
        if (($result['liked'] ?? false) && auth()->id()) {
            \App\Models\AnalyticsEvent::track(
                'post_like',
                'post',
                $post->id,
                auth()->id()
            );
        }
        
        return response()->json($result);
    }

    public function unlike(Post $post): JsonResponse
    {
        $result = $this->postService->toggleLike($post, auth()->user());
        return response()->json($result);
    }

    public function likes(Post $post): JsonResponse
    {
        $likes = $post->likes()->with('user:id,name,username,avatar')->paginate(config('pagination.likes'));
        return response()->json($likes);
    }

    public function timeline(): JsonResponse
    {
        $timelineData = $this->postService->getUserTimeline(auth()->user());
        return response()->json([
            'data' => PostResource::collection($timelineData['data']),
            'cached' => $timelineData['cached'] ?? false,
            'optimized' => $timelineData['optimized'] ?? false
        ]);
    }

    public function drafts(): JsonResponse
    {
        $drafts = $this->postService->getUserDrafts(auth()->user());
        return response()->json(['data' => PostResource::collection($drafts)]);
    }

    /**
     * Get post edit history
     */
    public function editHistory(Post $post): JsonResponse
    {
        $this->authorize('view', $post);
        
        $history = $this->postService->getEditHistory($post);
        
        return response()->json($history);
    }

    /**
     * Create quote post
     */
    public function quote(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'content' => ['required', new ContentLength('post')]
        ]);
        
        $quoteDTO = QuotePostDTO::fromRequest(
            $request->only(['content']),
            auth()->user()->id,
            $post->id
        );
        
        $quotePost = $this->postService->createQuotePost($quoteDTO);
        
        return response()->json(new PostResource($quotePost), 201);
    }

    /**
     * Get quotes of a post
     */
    public function quotes(Post $post): JsonResponse
    {
        $quotes = $this->postService->getPostQuotes($post);
        return response()->json(['data' => PostResource::collection($quotes)]);
    }

    /**
     * Publish a draft post
     */
    public function publish(Post $post): JsonResponse
    {
        $this->authorize('update', $post);
        
        if (!$post->is_draft) {
            return response()->json(['error' => 'Post is already published'], Response::HTTP_BAD_REQUEST);
        }
        
        $publishedPost = $this->postService->publishPost($post);
        
        return response()->json(new PostResource($publishedPost));
    }
}
