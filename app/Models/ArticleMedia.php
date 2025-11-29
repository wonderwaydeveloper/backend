<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'type',
        'order',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'order' => 'integer',
        'metadata' => 'array',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}