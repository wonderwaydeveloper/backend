<?php

namespace App\Services;

use MeiliSearch\Client;
use Illuminate\Support\Facades\Log;

class SearchService
{
    private $client;

    public function __construct()
    {
        $this->client = new Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );
    }

    public function searchPosts($query, $page = 1, $perPage = 20)
    {
        try {
            $results = $this->client->index('posts')->search($query, [
                'limit' => $perPage,
                'offset' => ($page - 1) * $perPage,
                'attributesToHighlight' => ['content'],
            ]);

            return [
                'data' => $results['hits'],
                'total' => $results['estimatedTotalHits'],
                'page' => $page,
            ];
        } catch (\Exception $e) {
            Log::error('Post search failed', ['error' => $e->getMessage()]);
            return ['data' => [], 'total' => 0];
        }
    }

    public function searchUsers($query, $page = 1, $perPage = 20)
    {
        try {
            $results = $this->client->index('users')->search($query, [
                'limit' => $perPage,
                'offset' => ($page - 1) * $perPage,
                'attributesToHighlight' => ['name', 'username'],
            ]);

            return [
                'data' => $results['hits'],
                'total' => $results['estimatedTotalHits'],
                'page' => $page,
            ];
        } catch (\Exception $e) {
            Log::error('User search failed', ['error' => $e->getMessage()]);
            return ['data' => [], 'total' => 0];
        }
    }

    public function searchHashtags($query, $page = 1, $perPage = 20)
    {
        try {
            $results = $this->client->index('hashtags')->search($query, [
                'limit' => $perPage,
                'offset' => ($page - 1) * $perPage,
            ]);

            return [
                'data' => $results['hits'],
                'total' => $results['estimatedTotalHits'],
                'page' => $page,
            ];
        } catch (\Exception $e) {
            Log::error('Hashtag search failed', ['error' => $e->getMessage()]);
            return ['data' => [], 'total' => 0];
        }
    }

    public function indexPost($post)
    {
        try {
            $this->client->index('posts')->addDocuments([
                [
                    'id' => $post->id,
                    'content' => $post->content,
                    'user_id' => $post->user_id,
                    'created_at' => $post->created_at->timestamp,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Post indexing failed', ['error' => $e->getMessage()]);
        }
    }

    public function indexUser($user)
    {
        try {
            $this->client->index('users')->addDocuments([
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'bio' => $user->bio,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('User indexing failed', ['error' => $e->getMessage()]);
        }
    }

    public function deletePost($postId)
    {
        try {
            $this->client->index('posts')->deleteDocument($postId);
        } catch (\Exception $e) {
            Log::error('Post deletion failed', ['error' => $e->getMessage()]);
        }
    }
}
