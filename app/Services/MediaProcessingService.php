<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class MediaProcessingService
{
    private array $imageSizes = [
        'thumb' => [150, 150],
        'small' => [300, 300], 
        'medium' => [600, 600],
        'large' => [1200, 1200]
    ];

    public function processImage(UploadedFile $file): array
    {
        $results = [];
        $manager = new ImageManager(['driver' => 'gd']);
        
        foreach ($this->imageSizes as $size => $dimensions) {
            $image = $manager->make($file->path());
            $image->fit($dimensions[0], $dimensions[1]);
            
            $filename = $size . '_' . time() . '.jpg';
            $path = "images/{$filename}";
            
            Storage::put($path, $image->encode('jpg', 85));
            $results[$size] = $path;
        }
        
        return $results;
    }

    public function processVideo(UploadedFile $file): array
    {
        $filename = 'video_' . time() . '.mp4';
        $path = "videos/{$filename}";
        
        Storage::putFileAs('videos', $file, $filename);
        
        return [
            'original' => $path,
            'thumbnail' => $this->generateVideoThumbnail($path)
        ];
    }

    private function generateVideoThumbnail(string $videoPath): string
    {
        $thumbnailPath = "thumbnails/" . basename($videoPath, '.mp4') . '.jpg';
        // Simple placeholder - in production use FFmpeg
        Storage::put($thumbnailPath, file_get_contents(public_path('placeholder.jpg')));
        return $thumbnailPath;
    }
}