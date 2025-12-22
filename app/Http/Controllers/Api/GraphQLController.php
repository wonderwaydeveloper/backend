<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class GraphQLController extends Controller
{
    public function query(Request $request)
    {
        $query = $request->input('query');
        $variables = $request->input('variables', []);
        
        try {
            $result = $this->executeQuery($query, $variables);
            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [['message' => $e->getMessage()]]
            ], 400);
        }
    }

    private function executeQuery(string $query, array $variables): array
    {
        // Simple GraphQL parser for basic queries
        if (preg_match('/posts\s*\{([^}]+)\}/', $query, $matches)) {
            return $this->resolvePosts($matches[1], $variables);
        }
        
        if (preg_match('/user\(id:\s*(\d+)\)\s*\{([^}]+)\}/', $query, $matches)) {
            return $this->resolveUser((int)$matches[1], $matches[2]);
        }
        
        if (preg_match('/timeline\s*\{([^}]+)\}/', $query, $matches)) {
            return $this->resolveTimeline($matches[1], $variables);
        }
        
        throw new \Exception('Unsupported query');
    }

    private function resolvePosts(string $fields, array $variables): array
    {
        $limit = $variables['limit'] ?? 20;
        $posts = Post::published()
            ->with(['user:id,name,username,avatar'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->limit($limit)
            ->get();

        return [
            'posts' => $posts->map(function ($post) use ($fields) {
                return $this->selectFields($post, $fields);
            })->toArray()
        ];
    }

    private function resolveUser(int $userId, string $fields): array
    {
        $user = User::with(['posts' => function ($query) {
            $query->published()->latest()->limit(10);
        }])->findOrFail($userId);

        return [
            'user' => $this->selectFields($user, $fields)
        ];
    }

    private function resolveTimeline(string $fields, array $variables): array
    {
        $user = auth()->user();
        if (!$user) {
            throw new \Exception('Authentication required');
        }

        $followingIds = $user->following()->pluck('id');
        $posts = Post::whereIn('user_id', $followingIds)
            ->orWhere('user_id', $user->id)
            ->published()
            ->with(['user:id,name,username,avatar'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->limit($variables['limit'] ?? 20)
            ->get();

        return [
            'timeline' => $posts->map(function ($post) use ($fields) {
                return $this->selectFields($post, $fields);
            })->toArray()
        ];
    }

    private function selectFields($model, string $fields): array
    {
        $result = [];
        $fieldList = array_map('trim', explode(',', str_replace(['{', '}'], '', $fields)));
        
        foreach ($fieldList as $field) {
            if (strpos($field, 'user') === 0 && $model->relationLoaded('user')) {
                $result['user'] = [
                    'id' => $model->user->id,
                    'name' => $model->user->name,
                    'username' => $model->user->username,
                    'avatar' => $model->user->avatar,
                ];
            } elseif (property_exists($model, $field) || $model->hasAttribute($field)) {
                $result[$field] = $model->$field;
            }
        }
        
        return $result;
    }
}