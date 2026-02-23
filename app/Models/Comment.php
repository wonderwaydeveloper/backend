<?php

namespace App\Models;

use App\Traits\Mentionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory;
    use Mentionable;
    use SoftDeletes;

    protected $fillable = [
        'content',
        'is_pinned',
        'is_hidden',
    ];

    protected $guarded = [
        'id',
        'user_id',
        'post_id',
        'parent_id',
        'likes_count',
        'replies_count',
        'view_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'replies_count' => 'integer',
        'view_count' => 'integer',
        'is_pinned' => 'boolean',
        'is_hidden' => 'boolean',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
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
        
        $maxLength = config('content.validation.content.comment.max_length', 280);
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

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
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
            ->whereNull('parent_id')
            ->withUser()
            ->withCounts()
            ->latest();
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    public function isEdited()
    {
        return $this->edited_at !== null;
    }

    public function markAsEdited()
    {
        $this->edited_at = now();
        $this->save();
    }
}
