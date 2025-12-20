<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    protected $fillable = [
        'user_id',
        'media_type',
        'media_url',
        'caption',
        'views_count',
        'expires_at',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }
}
