<?php

namespace App\Services;

use App\Contracts\Services\FileUploadServiceInterface;
use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService implements FileUploadServiceInterface
{
    public function uploadImage(UploadedFile $image): string
    {
        return $image->store('posts', 'public');
    }
    
    public function uploadVideo(UploadedFile $video, Post $post): void
    {
        app(\App\Services\VideoUploadService::class)->uploadVideo($video, $post);
    }
    
    public function deleteFile(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }
}