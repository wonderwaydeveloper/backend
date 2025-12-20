<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hashtag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HashtagController extends Controller
{
    public function trending()
    {
        $hashtags = Cache::remember('trending_hashtags', 3600, function () {
            return Hashtag::orderBy('posts_count', 'desc')
                ->take(10)
                ->get();
        });

        return response()->json($hashtags);
    }

    public function show(Hashtag $hashtag)
    {
        $cacheKey = "hashtag:{$hashtag->id}:posts:page:" . request('page', 1);
        
        $posts = Cache::remember($cacheKey, 1800, function () use ($hashtag) {
            return $hashtag->posts()
                ->published()
                ->with([
                    'user:id,name,username,avatar',
                    'hashtags:id,name,slug'
                ])
                ->withCount('likes', 'comments')
                ->latest('published_at')
                ->paginate(20);
        });

        return response()->json([
            'hashtag' => $hashtag,
            'posts' => $posts,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        $cacheKey = "hashtag:search:" . md5($query);
        
        $hashtags = Cache::remember($cacheKey, 900, function () use ($query) {
            return Hashtag::where('name', 'like', "%{$query}%")
                ->orWhere('slug', 'like', "%{$query}%")
                ->orderBy('posts_count', 'desc')
                ->limit(20)
                ->get(['id', 'name', 'slug', 'posts_count']);
        });

        return response()->json($hashtags);
    }
}
