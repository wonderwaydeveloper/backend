<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use App\Jobs\GenerateThumbnailJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MediaService
{
    public function __construct(private FileValidationService $validator)
    {
    }

    public function findById(int $id): ?Media
    {
        return Media::find($id);
    }

    public function uploadImage($file, User $user, ?string $altText = null, ?string $type = 'post')
    {
        $this->validator->validateImage($file);
        
        return \DB::transaction(function () use ($file, $user, $altText, $type) {
            try {
                $filename = $this->generateFilename('webp');
                $path = "media/images/" . date('Y/m/d');
                
                $processedImage = $this->processImage($file, $type, 85);
                $fullPath = "{$path}/{$filename}";
                
                Storage::disk('public')->put($fullPath, $processedImage);
                
                if (!Storage::disk('public')->exists($fullPath)) {
                    throw new \Exception('Failed to save image file');
                }
                
                $url = Storage::disk('public')->url($fullPath);
                $dimensions = $this->getImageDimensions($processedImage);
                
                $media = Media::create([
                    'user_id' => $user->id,
                    'type' => 'image',
                    'path' => $fullPath,
                    'url' => $url,
                    'filename' => $filename,
                    'mime_type' => 'image/webp',
                    'size' => strlen($processedImage),
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'alt_text' => $altText,
                ]);
                
                \App\Jobs\GenerateImageVariantsJob::dispatch($media);
                
                return $media;
                
            } catch (\Exception $e) {
                if (isset($fullPath) && Storage::disk('public')->exists($fullPath)) {
                    Storage::disk('public')->delete($fullPath);
                }
                throw $e;
            }
        });
    }

    public function uploadVideo($file, User $user, ?string $type = 'post')
    {
        $this->validator->validateVideo($file);
        
        return \DB::transaction(function () use ($file, $user, $type) {
            try {
                $filename = $this->generateFilename($file->getClientOriginalExtension());
                $path = "media/{$type}s/videos/" . date('Y/m/d');
                $fullPath = "{$path}/{$filename}";
                
                Storage::disk('public')->putFileAs($path, $file, $filename);
                
                if (!Storage::disk('public')->exists($fullPath)) {
                    throw new \Exception('Failed to save video file');
                }
                
                $url = Storage::disk('public')->url($fullPath);
                
                $media = Media::create([
                    'user_id' => $user->id,
                    'type' => 'video',
                    'path' => $fullPath,
                    'url' => $url,
                    'filename' => $filename,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'encoding_status' => 'pending',
                    'processing_progress' => 0,
                ]);

                \App\Jobs\ProcessVideoJob::dispatch($media);

                return $media;
                
            } catch (\Exception $e) {
                if (isset($fullPath) && Storage::disk('public')->exists($fullPath)) {
                    Storage::disk('public')->delete($fullPath);
                }
                throw $e;
            }
        });
    }

    public function uploadDocument($file, User $user)
    {
        $this->validator->validateDocument($file);
        
        $filename = $this->generateFilename($file->getClientOriginalExtension());
        $path = "media/documents/" . date('Y/m/d');
        $fullPath = "{$path}/{$filename}";
        
        Storage::disk('public')->putFileAs($path, $file, $filename);
        
        $url = Storage::disk('public')->url($fullPath);
        
        return Media::create([
            'user_id' => $user->id,
            'type' => 'document',
            'path' => $fullPath,
            'url' => $url,
            'filename' => $filename,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function deleteMedia(Media $media)
    {
        if (Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }
        
        if ($media->thumbnail_url) {
            $thumbnailPath = str_replace('/storage/', '', parse_url($media->thumbnail_url, PHP_URL_PATH));
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }

        if ($media->image_variants) {
            foreach ($media->image_variants as $variant) {
                $variantPath = str_replace('/storage/', '', parse_url($variant, PHP_URL_PATH));
                if (Storage::disk('public')->exists($variantPath)) {
                    Storage::disk('public')->delete($variantPath);
                }
            }
        }

        if ($media->video_qualities) {
            foreach ($media->video_qualities as $quality) {
                $qualityPath = str_replace('/storage/', '', parse_url($quality, PHP_URL_PATH));
                if (Storage::disk('public')->exists($qualityPath)) {
                    Storage::disk('public')->delete($qualityPath);
                }
            }
        }
        
        $media->delete();
    }

    public function getUserMedia(User $user, ?string $type = null)
    {
        $query = Media::where('user_id', $user->id);
        
        if ($type) {
            $query->where('type', $type);
        }
        
        return $query->latest()->get();
    }

    public function attachToModel(Media $media, $model)
    {
        $media->update([
            'mediable_type' => get_class($model),
            'mediable_id' => $model->id,
        ]);
    }

    private function generateFilename($extension)
    {
        return Str::uuid() . '.' . $extension;
    }

    private function processImage($file, $type, $quality)
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file);

        switch ($type) {
            case 'avatar':
                $image->cover(400, 400);
                break;
            case 'cover':
                $image->cover(1200, 400);
                break;
            case 'story':
                $image->cover(1080, 1920);
                break;
            case 'post':
            default:
                if ($image->width() > 4096 || $image->height() > 4096) {
                    $image->scale(width: 4096);
                }
                break;
        }

        return $image->toWebp($quality)->toString();
    }

    private function getImageDimensions($imageData)
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($imageData);

        return [
            'width' => $image->width(),
            'height' => $image->height(),
        ];
    }

    public function generateThumbnail(Media $media)
    {
        if (!$media->isImage()) {
            return;
        }

        $imageContent = Storage::disk('public')->get($media->path);
        $manager = new ImageManager(new Driver());
        $thumbnail = $manager->read($imageContent);
        $thumbnail->cover(300, 300);

        $pathInfo = pathinfo($media->path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['basename'];
        
        Storage::disk('public')->put($thumbnailPath, $thumbnail->toJpeg(80)->toString());
        
        $media->update([
            'thumbnail_url' => Storage::disk('public')->url($thumbnailPath),
        ]);
    }
}
