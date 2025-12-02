<?php

namespace App\Http\Controllers;

use App\Http\Resources\GenericResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\ArticleResource;
use App\Services\BookmarkService;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function __construct(private BookmarkService $bookmarkService)
    {
    }

    /**
     * دریافت بوکمارک‌های کاربر
     */
    public function index(Request $request)
    {
        try {
            $bookmarks = $this->bookmarkService->getUserBookmarks($request->user(), $request->all());

            $resources = $bookmarks->map(function ($bookmark) {
                $bookmarkable = $bookmark->bookmarkable;
                if ($bookmarkable instanceof \App\Models\Post) {
                    return new PostResource($bookmarkable->load('user', 'media'));
                } elseif ($bookmarkable instanceof \App\Models\Article) {
                    return new ArticleResource($bookmarkable->load('user', 'media'));
                }
                return $bookmarkable;
            });

            return GenericResource::success($resources, 'Bookmarks retrieved successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * حذف بوکمارک
     */

    public function destroy(Request $request, $bookmarkableType, $bookmarkableId)
    {
        try {
            $deleted = $this->bookmarkService->removeBookmark(
                $request->user(),
                $bookmarkableType,
                $bookmarkableId
            );

            return GenericResource::success([
                'deleted' => $deleted,
            ], 'Bookmark removed successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return GenericResource::error('Bookmark not found', 404);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

}