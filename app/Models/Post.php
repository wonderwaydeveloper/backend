<?php

namespace App\Models;

use App\Traits\Mentionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use HasFactory, Searchable, Mentionable;

    protected $guarded = ['id'];

    protected $fillable = [
        'user_id',
        'content',
        'image',
        'gif_url',
        'likes_count',
        'comments_count',
        'is_draft',
        'published_at',
        'reply_settings',
        'thread_id',
        'thread_position',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'is_draft' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class)->withTimestamps();
    }

    public function syncHashtags()
    {
        $hashtags = Hashtag::createFromText($this->content);
        $hashtagIds = collect($hashtags)->pluck('id')->toArray();
        
        $this->hashtags()->sync($hashtagIds);
        
        foreach ($hashtags as $hashtag) {
            $hashtag->update(['posts_count' => $hashtag->posts()->count()]);
        }
    }



    public function reposts()
    {
        return $this->hasMany(Repost::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_draft', false);
    }

    public function scopeDrafts($query)
    {
        return $query->where('is_draft', true);
    }

    public function thread()
    {
        return $this->belongsTo(Post::class, 'thread_id');
    }

    public function threadPosts()
    {
        return $this->hasMany(Post::class, 'thread_id')->orderBy('thread_position');
    }

    public function poll()
    {
        return $this->hasOne(Poll::class);
    }

    public function hasPoll(): bool
    {
        return $this->poll()->exists();
    }

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'user_name' => $this->user->name,
            'user_username' => $this->user->username,
            'hashtags' => $this->hashtags->pluck('name')->toArray(),
            'created_at' => $this->created_at->timestamp,
            'is_draft' => $this->is_draft,
        ];
    }

    public function shouldBeSearchable()
    {
        return !$this->is_draft;
    }
}
