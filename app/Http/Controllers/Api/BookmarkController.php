<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request)
    {
        $bookmarks = $request->user()
            ->bookmarks()
            ->with('post.user:id,name,username,avatar')
            ->latest()
            ->paginate(20);

        return response()->json($bookmarks);
    }

    public function toggle(Post $post)
    {
        $user = auth()->user();
        $bookmark = $user->bookmarks()->where('post_id', $post->id)->first();

        if ($bookmark) {
            $bookmark->delete();
            $bookmarked = false;
        } else {
            $user->bookmarks()->create(['post_id' => $post->id]);
            $bookmarked = true;
        }

        return response()->json(['bookmarked' => $bookmarked]);
    }
}
