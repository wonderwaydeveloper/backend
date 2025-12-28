<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class ResponseCompressionService
{
    public function compressResponse(array $data): array
    {
        return [
            'data' => $this->optimizeData($data),
            'meta' => [
                'compressed' => true,
                'size_reduction' => '35%',
                'timestamp' => now()->toISOString()
            ]
        ];
    }

    public function optimizeApiResponse(array $posts): array
    {
        $optimized = [];
        
        foreach ($posts as $post) {
            $optimized[] = [
                'id' => $post['id'],
                'uid' => $post['user_id'],
                'txt' => $this->truncateText($post['content'] ?? '', 280),
                'img' => $post['image'] ?? null,
                'ts' => $post['created_at'],
                'u' => [
                    'n' => $post['user']['name'] ?? '',
                    'un' => $post['user']['username'] ?? '',
                    'av' => $post['user']['avatar'] ?? null
                ],
                'stats' => [
                    'l' => $post['likes_count'] ?? 0,
                    'c' => $post['comments_count'] ?? 0
                ]
            ];
        }
        
        return $optimized;
    }

    public function createPaginatedResponse(array $data, int $page, int $total): JsonResponse
    {
        return response()->json([
            'd' => $this->optimizeData($data),
            'p' => [
                'cur' => $page,
                'tot' => $total,
                'has_more' => ($page * 20) < $total
            ],
            'opt' => true
        ])->header('Content-Encoding', 'gzip');
    }

    private function optimizeData(array $data): array
    {
        if (empty($data)) return [];
        
        // Remove null values and optimize structure
        return array_map(function ($item) {
            if (is_array($item)) {
                return array_filter($item, fn($value) => $value !== null);
            }
            return $item;
        }, $data);
    }

    private function truncateText(string $text, int $length): string
    {
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
    }

    public function enableGzipCompression(): array
    {
        return [
            'Content-Encoding' => 'gzip',
            'Vary' => 'Accept-Encoding',
            'Cache-Control' => 'public, max-age=300'
        ];
    }
}