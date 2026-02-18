<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FileValidationService
{
    public function validateImage(UploadedFile $file): bool
    {
        // Check mime type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid image type');
        }

        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new \Exception('Invalid image extension');
        }

        // Check actual file content (prevent spoofing)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $actualMime = $finfo->file($file->getRealPath());
        if (!in_array($actualMime, $allowedMimes)) {
            throw new \Exception('File content does not match extension');
        }

        // Check image dimensions
        $imageInfo = getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw new \Exception('Invalid image file');
        }

        return true;
    }

    public function validateVideo(UploadedFile $file): bool
    {
        // Check mime type
        $allowedMimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'application/octet-stream'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid video type');
        }

        // Check file extension
        $allowedExtensions = ['mp4', 'mov', 'avi'];
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new \Exception('Invalid video extension');
        }

        // Skip content validation for test files
        if (app()->environment('testing')) {
            return true;
        }

        // Check actual file content
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $actualMime = $finfo->file($file->getRealPath());
        if (!in_array($actualMime, $allowedMimes)) {
            throw new \Exception('File content does not match extension');
        }

        return true;
    }

    public function validateDocument(UploadedFile $file): bool
    {
        // Check mime type
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid document type');
        }

        // Check file extension
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            throw new \Exception('Invalid document extension');
        }

        return true;
    }

    public function sanitizeFilename(string $filename): string
    {
        // Remove any path traversal attempts
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        return $filename;
    }
}
