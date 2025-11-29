<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Http\Resources\GenericResource;
use App\Models\Article;
use App\Services\ArticleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function __construct(private ArticleService $articleService) {}

    /**
     * نمایش تمام مقالات
     */
    public function index(Request $request)
    {
        try {
            $articles = $this->articleService->getArticles($request->user(), $request->all());

            return GenericResource::success(
                ArticleResource::collection($articles),
                'Articles retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * ایجاد مقاله جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'sometimes|string|max:500',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'featured_image' => 'sometimes|image|max:5120',
            'status' => 'sometimes|in:draft,published,scheduled',
            'scheduled_at' => 'sometimes|date|after:now',
            'media' => 'sometimes|array',
            'media.*' => 'file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('create', Article::class);

            $article = $this->articleService->createArticle($request->user(), $request->all());

            return GenericResource::success(
                new ArticleResource($article->load('user', 'media')),
                'Article created successfully',
                201
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش مقاله خاص
     */
    public function show(Request $request, Article $article)
    {
        try {
            $this->authorize('view', $article);

            $article->load('user', 'media', 'approver');
            $article->incrementViewCount();

            return GenericResource::success(
                new ArticleResource($article),
                'Article retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 403);
        }
    }

    /**
     * آپدیت مقاله
     */
    public function update(Request $request, Article $article)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'excerpt' => 'sometimes|string|max:500',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'featured_image' => 'sometimes|image|max:5120',
            'status' => 'sometimes|in:draft,published,scheduled',
            'scheduled_at' => 'sometimes|date|after:now',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('update', $article);

            $article = $this->articleService->updateArticle($article, $request->all());

            return GenericResource::success(
                new ArticleResource($article->load('user', 'media')),
                'Article updated successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * حذف مقاله
     */
    public function destroy(Request $request, Article $article)
    {
        try {
            $this->authorize('delete', $article);

            $this->articleService->deleteArticle($article);

            return GenericResource::success(null, 'Article deleted successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * لایک کردن مقاله
     */
    public function like(Request $request, Article $article)
    {
        try {
            $this->authorize('like', $article);

            $liked = $this->articleService->toggleLike($request->user(), $article);

            return GenericResource::success([
                'liked' => $liked,
                'like_count' => $article->fresh()->like_count,
            ], $liked ? 'Article liked' : 'Article unliked');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * افزودن به بوکمارک
     */
    public function bookmark(Request $request, Article $article)
    {
        try {
            $bookmarked = $this->articleService->toggleBookmark($request->user(), $article);

            return GenericResource::success([
                'bookmarked' => $bookmarked,
            ], $bookmarked ? 'Article bookmarked' : 'Article removed from bookmarks');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * انتشار مقاله
     */
    public function publish(Request $request, Article $article)
    {
        try {
            $this->authorize('publish', $article);

            $article = $this->articleService->publishArticle($article);

            return GenericResource::success(
                new ArticleResource($article),
                'Article published successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * تایید مقاله (ادمین)
     */
    public function approve(Request $request, Article $article)
    {
        try {
            $this->authorize('approve', $article);

            $article = $this->articleService->approveArticle($article, $request->user());

            return GenericResource::success(
                new ArticleResource($article),
                'Article approved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت مقالات کاربر
     */
    public function userArticles(Request $request, $userId)
    {
        try {
            $articles = $this->articleService->getUserArticles($userId, $request->user(), $request->all());

            return GenericResource::success(
                ArticleResource::collection($articles),
                'User articles retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }
}