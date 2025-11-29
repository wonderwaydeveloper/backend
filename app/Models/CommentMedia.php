<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'type',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}