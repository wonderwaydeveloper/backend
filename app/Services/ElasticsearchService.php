<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;

class ElasticsearchService
{
    private string $host;
    private string $index;

    public function __construct()
    {
        $this->host = config('services.elasticsearch.host', 'localhost:9200');
        $this->index = config('services.elasticsearch.index', 'wonderway');
    }

    public function indexPost(Post $post): bool
    {
        $document = [
            'id' => $post->id,
            'content' => $post->content,
            'user_id' => $post->user_id,
            'user_name' => $post->user->name,
            'user_username' => $post->user->username,
            'hashtags' => $post->hashtags->pluck('name')->toArray(),
            'created_at' => $post->created_at->toISOString(),
            'likes_count' => $post->likes_count,
            'comments_count' => $post->comments_count,
            'has_media' => !empty($post->image) || !empty($post->gif_url),
        ];

        return $this->indexDocument('posts', $post->id, $document);
    }

    public function indexUser(User $user): bool
    {
        $document = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'bio' => $user->bio,
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
            'posts_count' => $user->posts()->count(),
            'is_verified' => $user->hasRole('verified'),
            'created_at' => $user->created_at->toISOString(),
        ];

        return $this->indexDocument('users', $user->id, $document);
    }

    public function searchPosts(string $query, array $filters = []): Collection
    {
        $searchQuery = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => ['content^2', 'hashtags^1.5', 'user_name', 'user_username'],
                                'type' => 'best_fields',
                                'fuzziness' => 'AUTO'
                            ]
                        ]
                    ],
                    'filter' => []
                ]
            ],
            'sort' => [
                ['_score' => ['order' => 'desc']],
                ['created_at' => ['order' => 'desc']]
            ],
            'size' => $filters['limit'] ?? 20
        ];

        // Add filters
        if (isset($filters['user_id'])) {
            $searchQuery['query']['bool']['filter'][] = [
                'term' => ['user_id' => $filters['user_id']]
            ];
        }

        if (isset($filters['has_media']) && $filters['has_media']) {
            $searchQuery['query']['bool']['filter'][] = [
                'term' => ['has_media' => true]
            ];
        }

        if (isset($filters['date_from'])) {
            $searchQuery['query']['bool']['filter'][] = [
                'range' => [
                    'created_at' => [
                        'gte' => $filters['date_from']
                    ]
                ]
            ];
        }

        $results = $this->search('posts', $searchQuery);
        
        // Handle empty or invalid response
        if (!isset($results['hits']['hits'])) {
            return collect();
        }
        
        // Get actual Post models
        $postIds = collect($results['hits']['hits'])->pluck('_source.id');
        return Post::whereIn('id', $postIds)
            ->with(['user:id,name,username,avatar'])
            ->withCount(['likes', 'comments'])
            ->get();
    }

    public function searchUsers(string $query, array $filters = []): Collection
    {
        $searchQuery = [
            'query' => [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['name^2', 'username^2', 'bio'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO'
                ]
            ],
            'sort' => [
                ['_score' => ['order' => 'desc']],
                ['followers_count' => ['order' => 'desc']]
            ],
            'size' => $filters['limit'] ?? 20
        ];

        $results = $this->search('users', $searchQuery);
        
        // Handle empty or invalid response
        if (!isset($results['hits']['hits'])) {
            return collect();
        }
        
        $userIds = collect($results['hits']['hits'])->pluck('_source.id');
        return User::whereIn('id', $userIds)
            ->withCount(['followers', 'following', 'posts'])
            ->get();
    }

    public function getSuggestions(string $query): array
    {
        $searchQuery = [
            'suggest' => [
                'post_suggest' => [
                    'prefix' => $query,
                    'completion' => [
                        'field' => 'content.suggest',
                        'size' => 5
                    ]
                ],
                'user_suggest' => [
                    'prefix' => $query,
                    'completion' => [
                        'field' => 'username.suggest',
                        'size' => 5
                    ]
                ]
            ]
        ];

        $results = $this->search('_all', $searchQuery);
        
        return [
            'posts' => $results['suggest']['post_suggest'][0]['options'] ?? [],
            'users' => $results['suggest']['user_suggest'][0]['options'] ?? [],
        ];
    }

    private function indexDocument(string $type, int $id, array $document): bool
    {
        $url = "http://{$this->host}/{$this->index}_{$type}/_doc/{$id}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($document));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }

    private function search(string $type, array $query): array
    {
        $url = "http://{$this->host}/{$this->index}_{$type}/_search";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true) ?? [];
    }

    public function deletePost(int $postId): bool
    {
        $url = "http://{$this->host}/{$this->index}_posts/_doc/{$postId}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
}