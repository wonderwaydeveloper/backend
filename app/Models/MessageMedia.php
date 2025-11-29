<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'type',
        'duration',
        'thumbnail',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'duration' => 'integer',
        'metadata' => 'array',
    ];

    public function message()
    {
        return $this->belongsTo(PrivateMessage::class, 'message_id');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : null;
    }
}