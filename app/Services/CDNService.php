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
        $filename = $this->generateUniqueFilename($file);
        $path = $folder . '/' . date('Y/m') . '/' . $filename;
        
        $uploaded = Storage::disk('public')->put($path, file_get_contents($file));
        
        if (!$uploaded) {
            throw new \Exception('Failed to upload to CDN');
        }
        
        return [
            'path' => $path,
            'url' => $this->getCDNUrl($path, 'images'),
            'thumbnail' => $this->getCDNUrl($this->generateThumbnail($path), 'images')
        ];
    }

    public function uploadVideo(UploadedFile $file, string $folder = 'videos'): array
    {
        $filename = $this->generateUniqueFilename($file);
        $path = $folder . '/' . date('Y/m') . '/' . $filename;
        
        $uploaded = Storage::disk('public')->put($path, file_get_contents($file));
        
        if (!$uploaded) {
            throw new \Exception('Failed to upload to CDN');
        }
        
        return [
            'path' => $path,
            'url' => $this->getCDNUrl($path, 'videos'),
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
        return Cache::remember('critical_assets', config('cache_ttl.ttl.critical_assets'), function () {
            return [
                'css' => ['/css/app.css'],
                'js' => ['/js/app.js'],
                'fonts' => ['/fonts/main.woff2']
            ];
        });
    }
}