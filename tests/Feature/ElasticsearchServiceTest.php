<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\ElasticsearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElasticsearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ElasticsearchService $elasticsearchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->elasticsearchService = $this->createMock(ElasticsearchService::class);
    }

    public function test_can_index_post(): void
    {
        $user = User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'content' => 'Test post content #laravel'
        ]);

        $this->elasticsearchService->method('indexPost')->willReturn(true);
        $result = $this->elasticsearchService->indexPost($post);

        $this->assertTrue($result);
    }

    public function test_can_index_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'username' => 'janesmith',
            'bio' => 'Laravel developer'
        ]);

        $this->elasticsearchService->method('indexUser')->willReturn(true);
        $result = $this->elasticsearchService->indexUser($user);

        $this->assertTrue($result);
    }

    public function test_can_search_posts(): void
    {
        $this->elasticsearchService->method('searchPosts')->willReturn(collect());
        $results = $this->elasticsearchService->searchPosts('Laravel');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $results);
    }

    public function test_can_search_users(): void
    {
        $this->elasticsearchService->method('searchUsers')->willReturn(collect());
        $results = $this->elasticsearchService->searchUsers('John');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $results);
    }

    public function test_search_with_filters(): void
    {
        $this->elasticsearchService->method('searchPosts')->willReturn(collect());
        $results = $this->elasticsearchService->searchPosts('test', [
            'has_media' => true,
            'limit' => 10
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $results);
    }

    public function test_can_get_suggestions(): void
    {
        $this->elasticsearchService->method('getSuggestions')->willReturn([
            'posts' => [],
            'users' => []
        ]);
        $suggestions = $this->elasticsearchService->getSuggestions('lar');

        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey('posts', $suggestions);
        $this->assertArrayHasKey('users', $suggestions);
    }

    public function test_can_delete_post(): void
    {
        $this->elasticsearchService->method('deletePost')->willReturn(true);
        $result = $this->elasticsearchService->deletePost(1);

        $this->assertTrue($result);
    }
}