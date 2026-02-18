<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'image',
            'path' => 'media/images/2024/01/01/test.webp',
            'url' => 'http://localhost/storage/media/images/2024/01/01/test.webp',
            'filename' => 'test.webp',
            'mime_type' => 'image/webp',
            'size' => 102400,
            'width' => 1200,
            'height' => 800,
            'encoding_status' => 'completed',
            'processing_progress' => 100,
        ];
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'video',
            'path' => 'media/videos/2024/01/01/test.mp4',
            'url' => 'http://localhost/storage/media/videos/2024/01/01/test.mp4',
            'filename' => 'test.mp4',
            'mime_type' => 'video/mp4',
            'duration' => 60,
            'encoding_status' => 'pending',
            'processing_progress' => 0,
        ]);
    }

    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'document',
            'path' => 'media/documents/2024/01/01/test.pdf',
            'url' => 'http://localhost/storage/media/documents/2024/01/01/test.pdf',
            'filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}
