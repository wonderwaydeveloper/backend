<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'commentable_id',
        'commentable_type',
        'content',
        'parent_id',
        'is_edited',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'like_count' => 'integer',
        'reply_count' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function media()
    {
        return $this->hasMany(CommentMedia::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    // Scopes
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // Methods
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    public function markAsEdited(): void
    {
        $this->update(['is_edited' => true]);
    }
}