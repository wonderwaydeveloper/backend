<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduledPost extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'content',
        'image',
        'scheduled_at',
        'status',
        'post_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', config('status.scheduled_post.pending'));
    }

    public function scopeReady($query)
    {
        return $query->pending()->where('scheduled_at', '<=', now());
    }
    
    public function scopeFailed($query)
    {
        return $query->where('status', config('status.scheduled_post.failed'));
    }

    public function scopePublished($query)
    {
        return $query->where('status', config('status.scheduled_post.published'));
    }
}
