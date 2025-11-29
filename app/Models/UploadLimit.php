<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'max_files',
        'max_file_size',
        'allowed_mimes',
        'max_total_size',
        'is_video_allowed',
        'max_video_duration',
        'max_video_size',
    ];

    protected $casts = [
        'max_files' => 'integer',
        'max_file_size' => 'integer',
        'allowed_mimes' => 'array',
        'max_total_size' => 'integer',
        'is_video_allowed' => 'boolean',
        'max_video_duration' => 'integer',
        'max_video_size' => 'integer',
    ];

    // Scopes
    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public static function getForType(string $type): self
    {
        return self::forType($type)->firstOrCreate([
            'type' => $type
        ], [
            'max_files' => 5,
            'max_file_size' => 10240,
            'allowed_mimes' => ['jpg', 'png', 'jpeg', 'gif'],
            'max_total_size' => 51200,
            'is_video_allowed' => true,
            'max_video_duration' => 300,
            'max_video_size' => 51200,
        ]);
    }

    public function isMimeAllowed(string $mime): bool
    {
        $extension = explode('/', $mime)[1] ?? '';
        return in_array($extension, $this->allowed_mimes);
    }

    public function isVideoMime(string $mime): bool
    {
        return str_starts_with($mime, 'video/');
    }

    public function isWithinVideoLimits(int $size, int $duration = null): bool
    {
        if (!$this->is_video_allowed) {
            return false;
        }

        if ($size > $this->max_video_size * 1024) {
            return false;
        }

        if ($duration && $duration > $this->max_video_duration) {
            return false;
        }

        return true;
    }
}