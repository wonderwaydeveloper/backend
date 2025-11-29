<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'type',
        'duration',
        'thumbnail',
        'order',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'duration' => 'integer',
        'order' => 'integer',
        'metadata' => 'array',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isGif(): bool
    {
        return $this->mime_type === 'image/gif';
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}