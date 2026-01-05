<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class GraphQLController extends Controller
{
    public function handle(Request $request)
    {
        $query = $request->input('query');
        $variables = $request->input('variables', []);

        if (empty($query)) {
            return response()->json(['errors' => ['message' => 'Query is required']], 400);
        }

        try {
            // Simple GraphQL parser for testing
            if (str_contains($query, 'posts')) {
                $posts = Post::with('user')->published()->latest()->take(10)->get();
                return response()->json([
                    'data' => [
                        'posts' => $posts->map(function ($post) {
                            return [
                                'id' => $post->id,
                                'content' => $post->content,
                                'user' => [
                                    'name' => $post->user->name,
                                    'username' => $post->user->username
                                ]
                            ];
                        })
                    ]
                ]);
            }

            if (str_contains($query, 'user')) {
                $user = $request->user();
                return response()->json([
                    'data' => [
                        'user' => [
                            'name' => $user->name,
                            'username' => $user->username
                        ]
                    ]
                ]);
            }

            if (str_contains($query, 'timeline')) {
                $posts = Post::with('user')->published()->latest()->take(10)->get();
                return response()->json([
                    'data' => [
                        'timeline' => $posts->map(function ($post) {
                            return [
                                'id' => $post->id,
                                'content' => $post->content,
                                'likes_count' => $post->likes_count
                            ];
                        })
                    ]
                ]);
            }

            return response()->json(['errors' => ['message' => 'Invalid query']], 400);

        } catch (\Exception $e) {
            return response()->json(['errors' => ['message' => $e->getMessage()]], 400);
        }
    }
}