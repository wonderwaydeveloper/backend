<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'content',
        'type',
        'parent_id',
        'original_post_id',
        'is_sensitive',
        'is_edited',
        'scheduled_at',
        'published_at',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
        'is_edited' => 'boolean',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'like_count' => 'integer',
        'reply_count' => 'integer',
        'repost_count' => 'integer',
        'view_count' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class);
    }

    public function parent()
    {
        return $this->belongsTo(Post::class, 'parent_id');
    }

    public function originalPost()
    {
        return $this->belongsTo(Post::class, 'original_post_id');
    }

    public function replies()
    {
        return $this->hasMany(Post::class, 'parent_id');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function bookmarks()
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '>', now());
    }

    public function scopeWithMedia($query)
    {
        return $query->whereHas('media');
    }

    public function scopeOriginal($query)
    {
        return $query->whereNull('parent_id');
    }

    // Methods
    public function isPublished(): bool
    {
        return $this->published_at && $this->published_at <= now();
    }

    public function isScheduled(): bool
    {
        return $this->scheduled_at && $this->scheduled_at > now();
    }

    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    public function isRepost(): bool
    {
        return !is_null($this->original_post_id);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function getExcerptAttribute($length = 100): string
    {
        return str($this->content)->limit($length);
    }
}