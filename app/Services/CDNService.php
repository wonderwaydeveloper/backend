<?php

namespace App\Services;

use Illuminate\Support\Facades\{Storage, Cache};
use Illuminate\Http\UploadedFile;

class CDNService
{
    private string $cdnUrl;

    public function __construct()
    {
        $this->cdnUrl = config('app.cdn_url', 'https://cdn-images.clevlance.com');
    }

    public function uploadToCDN(string $filePath): string
    {
        // Simulate CDN upload
        $cdnPath = "cdn/" . basename($filePath);
        
        if (Storage::exists($filePath)) {
            Storage::copy($filePath, $cdnPath);
        }
        
        return $this->getCDNUrl($cdnPath);
    }

    public function getCDNUrl(string $path, string $type = 'images'): string
    {
        if (app()->environment('local')) {
            return config('app.url') . '/storage/' . $path;
        }
        return $this->cdnUrl . '/' . $path;
    }

    public function uploadImage(UploadedFile $file, string $folder = 'images'): array
    {
        $disk = config('filesystems.default', 'public');
        $filename = $this->generateUniqueFilename($file);
        $path = $folder . '/' . date('Y/m') . '/' . $filename;
        
        $uploaded = Storage::disk($disk)->put($path, file_get_contents($file));
        
        if (!$uploaded) {
            throw new \Exception('Failed to upload to CDN');
        }
        
        $cdnUrl = config('filesystems.cdn_url');
        $url = $cdnUrl ? rtrim($cdnUrl, '/') . '/' . ltrim($path, '/') : $this->getCDNUrl($path, 'images');
        $thumbnailPath = $this->generateThumbnail($path);
        $thumbnailUrl = $cdnUrl ? rtrim($cdnUrl, '/') . '/' . ltrim($thumbnailPath, '/') : $this->getCDNUrl($thumbnailPath, 'images');
        
        return [
            'path' => $path,
            'url' => $url,
            'thumbnail' => $thumbnailUrl
        ];
    }

    public function uploadVideo(UploadedFile $file, string $folder = 'videos'): array
    {
        $disk = config('filesystems.default', 'public');
        $filename = $this->generateUniqueFilename($file);
        $path = $folder . '/' . date('Y/m') . '/' . $filename;
        
        $uploaded = Storage::disk($disk)->put($path, file_get_contents($file));
        
        if (!$uploaded) {
            throw new \Exception('Failed to upload to CDN');
        }
        
        $cdnUrl = config('filesystems.cdn_url');
        $url = $cdnUrl ? rtrim($cdnUrl, '/') . '/' . ltrim($path, '/') : $this->getCDNUrl($path, 'videos');
        
        return [
            'path' => $path,
            'url' => $url,
            'processing' => true
        ];
    }

    private function generateUniqueFilename(UploadedFile $file): string
    {
        return uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
    }

    private function generateThumbnail(string $path): string
    {
        return str_replace('.', '_thumb.', $path);
    }

    public function optimizeDelivery(array $files): array
    {
        $optimized = [];
        
        foreach ($files as $key => $file) {
            $optimized[$key] = [
                'url' => $this->getCDNUrl($file),
                'webp' => $this->getWebPVersion($file),
                'compressed' => true
            ];
        }
        
        return $optimized;
    }

    private function getWebPVersion(string $file): string
    {
        $webpPath = str_replace(['.jpg', '.png'], '.webp', $file);
        return $this->getCDNUrl($webpPath);
    }

    public function preloadCriticalAssets(): array
    {
        return Cache::remember('critical_assets', config('performance.cache.critical_assets'), function () {
            return [
                'css' => ['/css/app.css'],
                'js' => ['/js/app.js'],
                'fonts' => ['/fonts/main.woff2']
            ];
        });
    }
}