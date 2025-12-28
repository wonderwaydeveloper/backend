<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileSecurityService
{
    private array $allowedMimeTypes = [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'video' => ['video/mp4', 'video/webm', 'video/ogg'],
        'document' => ['application/pdf', 'text/plain']
    ];
    
    private array $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp',
        'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jar',
        'sh', 'py', 'pl', 'rb', 'cgi', 'htaccess', 'svg'
    ];
    
    public function validateFile(UploadedFile $file, string $type = 'image'): array
    {
        $errors = [];
        
        // Basic validation
        if (!$file->isValid()) {
            $errors[] = 'File upload failed';
            return $errors;
        }
        
        // Size check
        $maxSize = config('security.file_upload.max_size', 10485760);
        if ($file->getSize() > $maxSize) {
            $errors[] = 'File too large';
        }
        
        // Extension check
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, $this->dangerousExtensions)) {
            $errors[] = 'Dangerous file extension';
        }
        
        // MIME type check
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->allowedMimeTypes[$type] ?? [])) {
            $errors[] = 'Invalid file type';
        }
        
        // Content validation
        $contentErrors = $this->validateFileContent($file, $type);
        $errors = array_merge($errors, $contentErrors);
        
        return $errors;
    }
    
    private function validateFileContent(UploadedFile $file, string $type): array
    {
        $errors = [];
        $content = file_get_contents($file->getPathname());
        
        // Check for embedded scripts
        if ($this->containsScript($content)) {
            $errors[] = 'File contains malicious script';
        }
        
        // Type-specific validation
        switch ($type) {
            case 'image':
                $errors = array_merge($errors, $this->validateImageContent($file));
                break;
            case 'video':
                $errors = array_merge($errors, $this->validateVideoContent($file));
                break;
        }
        
        return $errors;
    }
    
    private function containsScript(string $content): bool
    {
        $patterns = [
            '/<script[^>]*>/i',
            '/<\?php/i',
            '/<%/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function validateImageContent(UploadedFile $file): array
    {
        $errors = [];
        
        // Check if it's actually an image
        $imageInfo = @getimagesize($file->getPathname());
        if (!$imageInfo) {
            $errors[] = 'Invalid image file';
            return $errors;
        }
        
        // Check dimensions
        [$width, $height] = $imageInfo;
        if ($width > 4096 || $height > 4096) {
            $errors[] = 'Image dimensions too large';
        }
        
        return $errors;
    }
    
    private function validateVideoContent(UploadedFile $file): array
    {
        $errors = [];
        
        // Basic video validation (would need FFmpeg for full validation)
        $content = file_get_contents($file->getPathname(), false, null, 0, 1024);
        
        // Check for video file signatures
        $videoSignatures = [
            'mp4' => [0x00, 0x00, 0x00, 0x18, 0x66, 0x74, 0x79, 0x70],
            'webm' => [0x1A, 0x45, 0xDF, 0xA3]
        ];
        
        $isValidVideo = false;
        foreach ($videoSignatures as $signature) {
            if (substr($content, 0, count($signature)) === pack('C*', ...$signature)) {
                $isValidVideo = true;
                break;
            }
        }
        
        if (!$isValidVideo) {
            $errors[] = 'Invalid video format';
        }
        
        return $errors;
    }
    
    public function sanitizeFilename(string $filename): string
    {
        // Remove path traversal
        $filename = basename($filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent double extensions
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Add timestamp to prevent conflicts
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        return $name . '_' . time() . '.' . $extension;
    }
    
    public function quarantineFile(UploadedFile $file, string $reason): string
    {
        $quarantinePath = 'quarantine/' . date('Y/m/d');
        $filename = $this->sanitizeFilename($file->getClientOriginalName());
        
        $path = $file->storeAs($quarantinePath, $filename, 'local');
        
        Log::warning('File quarantined', [
            'original_name' => $file->getClientOriginalName(),
            'quarantine_path' => $path,
            'reason' => $reason,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType()
        ]);
        
        return $path;
    }
}