<?php

namespace App\Contracts\Services;

use App\Models\Post;
use Illuminate\Http\UploadedFile;

interface FileUploadServiceInterface
{
    public function uploadImage(UploadedFile $image): string;
    
    public function uploadVideo(UploadedFile $video, Post $post): void;
    
    public function deleteFile(string $path): bool;
}