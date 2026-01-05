<?php

namespace Tests\Feature;

use App\Services\CDNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CDNServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CDNService $cdnService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cdnService = app(CDNService::class);
        Storage::fake('s3');
    }

    public function test_can_upload_image_to_cdn(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $result = $this->cdnService->uploadImage($file, 'posts');

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('thumbnail', $result);
        $this->assertStringContainsString('posts/', $result['path']);
        $this->assertStringContainsString('cdn-images.microblogging.com', $result['url']);
    }

    public function test_can_upload_video_to_cdn(): void
    {
        $file = UploadedFile::fake()->create('test.mp4', 1000, 'video/mp4');

        $result = $this->cdnService->uploadVideo($file, 'videos');

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('processing', $result);
        $this->assertTrue($result['processing']);
        $this->assertStringContainsString('videos/', $result['path']);
    }

    public function test_generates_unique_filenames(): void
    {
        $file1 = UploadedFile::fake()->image('test1.jpg');
        $file2 = UploadedFile::fake()->image('test2.jpg');

        $result1 = $this->cdnService->uploadImage($file1);
        $result2 = $this->cdnService->uploadImage($file2);

        $this->assertNotEquals($result1['path'], $result2['path']);
    }

    public function test_cdn_url_generation(): void
    {
        $path = 'posts/2024/12/test.jpg';
        
        $url = $this->cdnService->getCDNUrl($path, 'images');
        
        $this->assertEquals('https://cdn-images.microblogging.com/posts/2024/12/test.jpg', $url);
    }

    public function test_upload_failure_throws_exception(): void
    {
        Storage::shouldReceive('disk->put')->andReturn(false);
        
        $file = UploadedFile::fake()->image('test.jpg');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to upload to CDN');

        $this->cdnService->uploadImage($file);
    }
}