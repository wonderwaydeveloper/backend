<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GiphyService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.giphy.com/v1/gifs';

    public function __construct()
    {
        $this->apiKey = config('services.giphy.api_key', 'DEMO_API_KEY');
    }

    public function search($query, $limit = 20)
    {
        $response = Http::get("{$this->baseUrl}/search", [
            'api_key' => $this->apiKey,
            'q' => $query,
            'limit' => $limit,
            'rating' => 'g',
        ]);

        if ($response->successful()) {
            return $response->json()['data'];
        }

        return [];
    }

    public function trending($limit = 20)
    {
        $response = Http::get("{$this->baseUrl}/trending", [
            'api_key' => $this->apiKey,
            'limit' => $limit,
            'rating' => 'g',
        ]);

        if ($response->successful()) {
            return $response->json()['data'];
        }

        return [];
    }
}
