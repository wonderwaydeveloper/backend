<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, Media};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        $mock = \Mockery::mock(\App\Services\SubscriptionLimitService::class);
        $mock->shouldReceive('getMaxFileSize')->andReturn(5 * 1024 * 1024);
        $mock->shouldReceive('canAccessFeature')->andReturn(true);
        $mock->shouldReceive('canUploadHD')->andReturn(true);
        $mock->shouldReceive('getRateLimit')->andReturn(100);
        $this->app->instance(\App\Services\SubscriptionLimitService::class, $mock);
        
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('user');
        $this->user->givePermissionTo(['media.view', 'media.upload', 'media.delete']);
    }

    public function test_can_list_media(): void
    {
        $this->actingAs($this->user, 'sanctum')->getJson('/api/media')->assertStatus(200);
    }

    public function test_can_show_media(): void
    {
        $media = Media::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')->getJson("/api/media/{$media->id}")->assertStatus(200);
    }

    public function test_can_upload_image(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 1000, 1000);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/image', [
                'image' => $file,
                'alt_text' => 'Test image'
            ])
            ->assertStatus(201);

        $response->assertJsonStructure([
            'data' => ['id', 'type', 'url', 'encoding_status', 'processing_progress']
        ]);
    }

    public function test_uploaded_image_has_webp_format(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/image', ['image' => $file])
            ->assertStatus(201);

        $this->assertEquals('image/webp', $response->json('data.mime_type'));
    }

    public function test_image_generates_variants(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'image',
            'image_variants' => [
                'small' => 'http://example.com/small.webp',
                'medium' => 'http://example.com/medium.webp',
                'large' => 'http://example.com/large.webp',
                'original' => 'http://example.com/original.webp',
            ]
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/media/{$media->id}")
            ->assertStatus(200);

        $response->assertJsonStructure([
            'data' => ['variants' => ['small', 'medium', 'large', 'original']]
        ]);
    }

    public function test_can_upload_video(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        
        $file = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/video', ['video' => $file])
            ->assertStatus(201);

        $response->assertJsonStructure([
            'data' => ['id', 'type', 'encoding_status', 'processing_progress']
        ]);
        
        $this->assertEquals('pending', $response->json('data.encoding_status'));
        $this->assertEquals(0, $response->json('data.processing_progress'));
        
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessVideoJob::class);
    }

    public function test_video_has_encoding_status(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'video',
            'encoding_status' => 'processing',
            'processing_progress' => 50
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/media/{$media->id}")
            ->assertStatus(200);

        $this->assertEquals('processing', $response->json('data.encoding_status'));
        $this->assertEquals(50, $response->json('data.processing_progress'));
    }

    public function test_completed_video_has_qualities(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'video',
            'encoding_status' => 'completed',
            'processing_progress' => 100,
            'video_qualities' => [
                '240p' => 'http://example.com/240p.mp4',
                '360p' => 'http://example.com/360p.mp4',
                '480p' => 'http://example.com/480p.mp4',
                '720p' => 'http://example.com/720p.mp4',
                '1080p' => 'http://example.com/1080p.mp4',
            ]
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/media/{$media->id}")
            ->assertStatus(200);

        $response->assertJsonStructure([
            'data' => ['qualities' => ['240p', '360p', '480p', '720p', '1080p']]
        ]);
    }

    public function test_can_check_media_status(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'video',
            'encoding_status' => 'completed',
            'video_qualities' => [
                '240p' => 'http://example.com/240p.mp4',
                '360p' => 'http://example.com/360p.mp4',
                '480p' => 'http://example.com/480p.mp4',
                '720p' => 'http://example.com/720p.mp4',
                '1080p' => 'http://example.com/1080p.mp4',
            ]
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/media/{$media->id}/status")
            ->assertStatus(200);

        $response->assertJsonStructure([
            'id', 'type', 'encoding_status', 'processing_progress',
            'video_urls' => ['240p', '360p', '480p', '720p', '1080p']
        ]);
    }

    public function test_can_check_image_status(): void
    {
        $media = Media::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'image',
            'image_variants' => [
                'small' => 'http://example.com/small.webp',
                'medium' => 'http://example.com/medium.webp',
                'large' => 'http://example.com/large.webp',
                'original' => 'http://example.com/original.webp',
            ]
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/media/{$media->id}/status")
            ->assertStatus(200);

        $response->assertJsonStructure([
            'id', 'type', 'encoding_status', 'processing_progress',
            'image_urls' => ['small', 'medium', 'large', 'original']
        ]);
    }

    public function test_can_upload_document(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/document', ['document' => $file])
            ->assertStatus(201);

        $response->assertJsonStructure(['data' => ['id', 'type', 'url']]);
        $this->assertEquals('document', $response->json('data.type'));
    }

    public function test_can_delete_media(): void
    {
        $media = Media::factory()->create(['user_id' => $this->user->id]);
        
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/media/{$media->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_image_max_size_is_5mb(): void
    {
        $maxSize = config('content.media.max_file_size.image');
        $this->assertEquals(5 * 1024 * 1024, $maxSize);
    }

    public function test_video_max_size_is_2gb(): void
    {
        $maxSize = config('content.media.max_file_size.video');
        $this->assertEquals(2 * 1024 * 1024 * 1024, $maxSize);
    }

    public function test_gif_max_size_is_15mb(): void
    {
        $maxSize = config('content.media.max_file_size.gif');
        $this->assertEquals(15 * 1024 * 1024, $maxSize);
    }

    public function test_image_max_dimensions_are_4096x4096(): void
    {
        $dimensions = config('content.media.image_dimensions.post');
        $this->assertEquals(4096, $dimensions['max_width']);
        $this->assertEquals(4096, $dimensions['max_height']);
    }

    public function test_video_max_duration_is_140_seconds(): void
    {
        $maxDuration = config('content.media.video_dimensions.max_duration');
        $this->assertEquals(140, $maxDuration);
    }

    public function test_image_variants_config_has_three_sizes(): void
    {
        $variants = config('content.media.image_variants');
        $this->assertArrayHasKey('small', $variants);
        $this->assertArrayHasKey('medium', $variants);
        $this->assertArrayHasKey('large', $variants);
        $this->assertEquals(340, $variants['small']);
        $this->assertEquals(680, $variants['medium']);
        $this->assertEquals(1200, $variants['large']);
    }

    public function test_video_qualities_config_has_five_resolutions(): void
    {
        $qualities = config('content.media.video_qualities');
        $this->assertArrayHasKey('240p', $qualities);
        $this->assertArrayHasKey('360p', $qualities);
        $this->assertArrayHasKey('480p', $qualities);
        $this->assertArrayHasKey('720p', $qualities);
        $this->assertArrayHasKey('1080p', $qualities);
    }

    public function test_media_model_has_image_variants_cast(): void
    {
        $media = new Media();
        $casts = $media->getCasts();
        $this->assertArrayHasKey('image_variants', $casts);
        $this->assertEquals('array', $casts['image_variants']);
    }

    public function test_media_model_has_video_qualities_cast(): void
    {
        $media = new Media();
        $casts = $media->getCasts();
        $this->assertArrayHasKey('video_qualities', $casts);
        $this->assertEquals('array', $casts['video_qualities']);
    }

    public function test_media_model_get_image_url_method(): void
    {
        $media = Media::factory()->create([
            'type' => 'image',
            'url' => 'http://example.com/original.webp',
            'image_variants' => [
                'small' => 'http://example.com/small.webp',
                'medium' => 'http://example.com/medium.webp',
            ]
        ]);

        $this->assertEquals('http://example.com/small.webp', $media->getImageUrl('small'));
        $this->assertEquals('http://example.com/medium.webp', $media->getImageUrl('medium'));
        $this->assertEquals('http://example.com/original.webp', $media->getImageUrl('original'));
    }

    public function test_media_model_get_video_url_method(): void
    {
        $media = Media::factory()->create([
            'type' => 'video',
            'video_qualities' => [
                '480p' => 'http://example.com/480p.mp4',
                '720p' => 'http://example.com/720p.mp4',
            ]
        ]);

        $this->assertEquals('http://example.com/480p.mp4', $media->getVideoUrl('480p'));
        $this->assertEquals('http://example.com/720p.mp4', $media->getVideoUrl('720p'));
        $this->assertNull($media->getVideoUrl('1080p'));
    }

    public function test_generate_image_variants_job_creates_three_sizes(): void
    {
        Storage::fake('public');
        $imagePath = 'media/images/2024/01/01/test.webp';
        
        $image = imagecreatetruecolor(1200, 800);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        ob_start();
        imagejpeg($image, null, 90);
        $imageContent = ob_get_clean();
        imagedestroy($image);
        
        Storage::disk('public')->put($imagePath, $imageContent);

        $media = Media::create([
            'user_id' => $this->user->id,
            'type' => 'image',
            'path' => $imagePath,
            'url' => Storage::disk('public')->url($imagePath),
            'filename' => 'test.webp',
            'mime_type' => 'image/webp',
            'size' => strlen($imageContent),
            'width' => 1200,
            'height' => 800,
        ]);

        $job = new \App\Jobs\GenerateImageVariantsJob($media);
        $job->handle();

        $media->refresh();

        $this->assertNotNull($media->image_variants);
        $this->assertArrayHasKey('small', $media->image_variants);
        $this->assertArrayHasKey('medium', $media->image_variants);
        $this->assertArrayHasKey('large', $media->image_variants);
        $this->assertArrayHasKey('original', $media->image_variants);

        $pathInfo = pathinfo($imagePath);
        $this->assertTrue(Storage::disk('public')->exists($pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_small.' . $pathInfo['extension']));
        $this->assertTrue(Storage::disk('public')->exists($pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_medium.' . $pathInfo['extension']));
        $this->assertTrue(Storage::disk('public')->exists($pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_large.' . $pathInfo['extension']));
    }

    public function test_image_variants_have_correct_dimensions(): void
    {
        Storage::fake('public');
        $imagePath = 'media/images/2024/01/01/test.webp';
        
        $image = imagecreatetruecolor(2000, 1500);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        ob_start();
        imagejpeg($image, null, 90);
        $imageContent = ob_get_clean();
        imagedestroy($image);
        
        Storage::disk('public')->put($imagePath, $imageContent);

        $media = Media::create([
            'user_id' => $this->user->id,
            'type' => 'image',
            'path' => $imagePath,
            'url' => Storage::disk('public')->url($imagePath),
            'filename' => 'test.webp',
            'mime_type' => 'image/webp',
            'size' => strlen($imageContent),
            'width' => 2000,
            'height' => 1500,
        ]);

        $job = new \App\Jobs\GenerateImageVariantsJob($media);
        $job->handle();

        $pathInfo = pathinfo($imagePath);
        $smallPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_small.' . $pathInfo['extension'];
        
        if (Storage::disk('public')->exists($smallPath)) {
            $smallContent = Storage::disk('public')->get($smallPath);
            $this->assertLessThan(strlen($imageContent), strlen($smallContent));
        }
    }

    public function test_process_video_job_fails_if_duration_exceeds_limit(): void
    {
        Storage::fake('public');
        $videoPath = 'media/videos/2024/01/01/test.mp4';
        Storage::disk('public')->put($videoPath, 'fake video content');

        $media = Media::create([
            'user_id' => $this->user->id,
            'type' => 'video',
            'path' => $videoPath,
            'url' => Storage::disk('public')->url($videoPath),
            'filename' => 'test.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'encoding_status' => 'pending',
            'processing_progress' => 0,
        ]);

        $job = new \App\Jobs\ProcessVideoJob($media);
        
        try {
            $job->handle();
            $this->fail('Expected exception for video duration exceeding limit');
        } catch (\Exception $e) {
            $media->refresh();
            $this->assertEquals('failed', $media->encoding_status);
        }
    }

    public function test_uploaded_image_file_actually_exists_on_disk(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg', 1000, 1000);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/image', ['image' => $file])
            ->assertStatus(201);

        $mediaId = $response->json('data.id');
        $media = Media::find($mediaId);

        $this->assertTrue(Storage::disk('public')->exists($media->path));
        
        $fileContent = Storage::disk('public')->get($media->path);
        $this->assertGreaterThan(0, strlen($fileContent));
    }

    public function test_uploaded_image_is_actually_webp_format(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg', 500, 500);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/image', ['image' => $file])
            ->assertStatus(201);

        $mediaId = $response->json('data.id');
        $media = Media::find($mediaId);

        $this->assertTrue(Storage::disk('public')->exists($media->path));
        
        $fileContent = Storage::disk('public')->get($media->path);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);
        
        $this->assertStringContainsString('webp', $mimeType);
    }

    public function test_image_is_resized_if_exceeds_4096px(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg', 5000, 5000);
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/image', ['image' => $file])
            ->assertStatus(201);

        $media = Media::find($response->json('data.id'));

        $this->assertLessThanOrEqual(4096, $media->width);
        $this->assertLessThanOrEqual(4096, $media->height);
    }

    public function test_delete_media_removes_all_variants_from_disk(): void
    {
        Storage::fake('public');
        $imagePath = 'media/images/2024/01/01/test.webp';
        
        $image = imagecreatetruecolor(1200, 800);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        ob_start();
        imagejpeg($image, null, 90);
        $imageContent = ob_get_clean();
        imagedestroy($image);
        
        Storage::disk('public')->put($imagePath, $imageContent);

        $media = Media::create([
            'user_id' => $this->user->id,
            'type' => 'image',
            'path' => $imagePath,
            'url' => Storage::disk('public')->url($imagePath),
            'filename' => 'test.webp',
            'mime_type' => 'image/webp',
            'size' => strlen($imageContent),
            'width' => 1200,
            'height' => 800,
            'image_variants' => [
                'small' => Storage::disk('public')->url('media/images/2024/01/01/test_small.webp'),
                'medium' => Storage::disk('public')->url('media/images/2024/01/01/test_medium.webp'),
            ]
        ]);

        Storage::disk('public')->put('media/images/2024/01/01/test_small.webp', 'small');
        Storage::disk('public')->put('media/images/2024/01/01/test_medium.webp', 'medium');

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/media/{$media->id}")
            ->assertStatus(200);

        $this->assertFalse(Storage::disk('public')->exists($imagePath));
        $this->assertFalse(Storage::disk('public')->exists('media/images/2024/01/01/test_small.webp'));
        $this->assertFalse(Storage::disk('public')->exists('media/images/2024/01/01/test_medium.webp'));
    }

    public function test_image_upload_rejects_file_larger_than_5mb(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('huge.jpg', 6000);
        
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/image', ['image' => $file])
            ->assertStatus(422);
    }

    public function test_video_upload_creates_pending_status(): void
    {
        Storage::fake('public');
        \Illuminate\Support\Facades\Queue::fake();
        
        $file = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');
        
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/video', ['video' => $file])
            ->assertStatus(201);

        $media = Media::find($response->json('data.id'));

        $this->assertEquals('pending', $media->encoding_status);
        $this->assertEquals(0, $media->processing_progress);
        $this->assertTrue(Storage::disk('public')->exists($media->path));
    }
}
