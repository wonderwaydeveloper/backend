<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/media/upload/image', [
                'image' => $image,
                'type' => 'post'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['url', 'path', 'filename', 'size', 'type']
            ]);

        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    public function test_user_can_upload_video(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $video = UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/media/upload/video', [
                'video' => $video,
                'type' => 'post'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['url', 'path', 'filename', 'size', 'type']
            ]);
    }

    public function test_user_can_upload_document(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $document = UploadedFile::fake()->create('test.pdf', 512, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/media/upload/document', [
                'document' => $document
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['url', 'path', 'filename', 'size']
            ]);
    }

    public function test_image_upload_validates_file_type(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/media/upload/image', [
                'image' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_image_upload_validates_file_size(): void
    {
        $user = User::factory()->create();
        $largeImage = UploadedFile::fake()->create('large.jpg', 11000); // 11MB

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/media/upload/image', [
                'image' => $largeImage
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_user_can_delete_media(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        
        // Create a fake file
        $path = 'media/posts/2025/01/22/test.jpg';
        Storage::disk('public')->put($path, 'fake content');

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/media/delete', [
                'path' => $path
            ]);

        $response->assertStatus(200);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_guest_cannot_upload_media(): void
    {
        $image = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/media/upload/image', [
            'image' => $image
        ]);

        $response->assertStatus(401);
    }
}