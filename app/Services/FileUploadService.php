<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;

class FileUploadService
{
    protected $imageManager;
    protected $ffmpeg;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
        
        // برای پردازش ویدیو - اگر ffmpeg نصب است
        if (class_exists('FFMpeg\FFMpeg')) {
            $this->ffmpeg = FFMpeg::create();
        }
    }

    /**
     * آپلود مدیا برای پست
     */
    public function uploadPostMedia($file)
    {
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $originalName = $file->getClientOriginalName();

        // تشخیص نوع فایل
        $type = $this->getFileType($mimeType);

        // تولید نام فایل
        $fileName = $this->generateFileName($file, $type);

        // آپلود فایل
        $path = $file->storeAs("posts/media/{$type}", $fileName, 'public');

        $result = [
            'path' => $path,
            'name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $fileSize,
            'type' => $type,
        ];

        // پردازش بر اساس نوع فایل
        if (str_starts_with($mimeType, 'image/')) {
            $result = array_merge($result, $this->processImage($path));
        } elseif (str_starts_with($mimeType, 'video/')) {
            $result = array_merge($result, $this->processVideo($path));
        }

        return $result;
    }

    /**
     * تشخیص نوع فایل
     */
    protected function getFileType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif ($mimeType === 'image/gif') {
            return 'gif';
        } else {
            return 'document';
        }
    }

    /**
     * تولید نام فایل
     */
    protected function generateFileName($file, $type)
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->timestamp;
        $random = bin2hex(random_bytes(8));

        return "{$type}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * پردازش تصویر
     */
    protected function processImage($path)
    {
        $image = $this->imageManager->read(Storage::disk('public')->path($path));
        
        // تغییر سایز اگر تصویر بزرگ است
        $image->scaleDown(1200, 1200);
        
        // ذخیره تصویر پردازش شده
        $image->save(Storage::disk('public')->path($path), 85);

        $metadata = [
            'width' => $image->width(),
            'height' => $image->height(),
        ];

        return ['metadata' => $metadata];
    }

    /**
     * پردازش ویدیو
     */
    protected function processVideo($path)
    {
        $result = [];
        
        if ($this->ffmpeg) {
            try {
                $video = $this->ffmpeg->open(Storage::disk('public')->path($path));
                
                // دریافت مدت زمان ویدیو
                $duration = $video->getFormat()->get('duration');
                $result['duration'] = (int) $duration;

                // ایجاد تامبنیل
                $thumbnailPath = $this->generateVideoThumbnail($video, $path);
                $result['thumbnail'] = $thumbnailPath;

                $result['metadata'] = [
                    'duration' => $duration,
                    'format' => $video->getFormat()->get('format_name'),
                ];

            } catch (\Exception $e) {
                // اگر پردازش ویدیو失败 شد، ادامه بده
                \Log::error('Video processing failed: ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * ایجاد تامبنیل برای ویدیو
     */
    protected function generateVideoThumbnail($video, $videoPath)
    {
        $thumbnailPath = str_replace('.mp4', '_thumb.jpg', $videoPath);
        
        $frame = $video->frame(TimeCode::fromSeconds(1));
        $frame->save(Storage::disk('public')->path($thumbnailPath));

        return $thumbnailPath;
    }

    /**
     * حذف فایل
     */
    public function deleteFile($path)
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return true;
        }
        return false;
    }

    /**
     * بررسی محدودیت‌های آپلود
     */
    public function checkUploadLimits($files, $type = 'post')
    {
        // در اینجا می‌توانید محدودیت‌ها از دیتابیس بخوانید
        $limits = [
            'post' => ['max_files' => 5, 'max_size' => 10240], // 10MB
            'article' => ['max_files' => 10, 'max_size' => 20480], // 20MB
            'message' => ['max_files' => 3, 'max_size' => 5120], // 5MB
        ];

        $limit = $limits[$type] ?? $limits['post'];

        if (count($files) > $limit['max_files']) {
            throw new \Exception("تعداد فایل‌ها نمی‌تواند بیشتر از {$limit['max_files']} باشد");
        }

        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += $file->getSize();
        }

        if ($totalSize > $limit['max_size'] * 1024) {
            throw new \Exception("حجم کل فایل‌ها نمی‌تواند بیشتر از {$limit['max_size']} کیلوبایت باشد");
        }

        return true;
    }
}