<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mediable_type',
        'mediable_id',
        'type',
        'path',
        'url',
        'thumbnail_url',
        'filename',
        'mime_type',
        'size',
        'width',
        'height',
        'duration',
        'alt_text',
        'encoding_status',
        'video_qualities',
        'image_variants',
        'processing_progress',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'video_qualities' => 'array',
        'image_variants' => 'array',
        'processing_progress' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mediable()
    {
        return $this->morphTo();
    }

    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    public function scopeDocuments($query)
    {
        return $query->where('type', 'document');
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    public function isProcessed(): bool
    {
        return $this->encoding_status === 'completed';
    }

    public function getVideoUrl(string $quality): ?string
    {
        return $this->video_qualities[$quality] ?? null;
    }

    public function getImageUrl(string $size): ?string
    {
        return $this->image_variants[$size] ?? $this->url;
    }
}
