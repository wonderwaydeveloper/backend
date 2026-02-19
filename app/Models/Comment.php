<?php

namespace App\Models;

use App\Traits\Mentionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    use Mentionable;

    protected $fillable = [
        'user_id',
        'post_id',
        'content',
    ];

    protected $guarded = ['id', 'likes_count'];

    protected $casts = [
        'likes_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function setContentAttribute($value)
    {
        // Remove script tags and their content completely
        $value = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value);
        // Remove all HTML tags
        $value = strip_tags($value);
        // Trim whitespace
        $value = trim($value);
        
        if (empty($value)) {
            throw new \InvalidArgumentException('Content cannot be empty');
        }
        
        $maxLength = config('validation.content.comment.max_length', 280);
        if (strlen($value) > $maxLength) {
            throw new \InvalidArgumentException("Content exceeds {$maxLength} characters");
        }
        
        $this->attributes['content'] = $value;
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'username', 'avatar']);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    // Query Scopes
    public function scopeWithUser($query)
    {
        return $query->with(['user:id,name,username,avatar']);
    }

    public function scopeWithCounts($query)
    {
        return $query->withCount(['likes']);
    }

    public function scopeForPost($query, $postId)
    {
        return $query->where('post_id', $postId)
            ->withUser()
            ->withCounts()
            ->latest();
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
