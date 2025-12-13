<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'bio',
        'avatar',
        'cover_image',
        'website',
        'location',
        'birth_date',
        'is_underage',
        'is_private',
        'is_verified',
        'is_banned',
        'provider',
        'provider_id',
        'email_verified_at',
        'phone_verified_at',
        'parent_id',
        'status', // اضافه شد
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'is_underage' => 'boolean',
            'is_private' => 'boolean',
            'is_verified' => 'boolean',
            'is_banned' => 'boolean',
            'last_login_at' => 'datetime',
            'followers_count' => 'integer',
            'following_count' => 'integer',
            'posts_count' => 'integer',
            'status' => 'string', // اضافه شد
        ];
    }

    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function parentalControls()
    {
        return $this->hasMany(ParentalControl::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ParentalControl::class, 'child_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function securityLogs()
    {
        return $this->hasMany(UserSecurityLog::class);
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->withTimestamps();
    }

    public function sentMessages()
    {
        return $this->hasMany(PrivateMessage::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('is_banned', false);
    }

    public function scopeVerified($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('email_verified_at')
                ->orWhereNotNull('phone_verified_at');
        });
    }

    public function scopeUnderage($query)
    {
        return $query->where('is_underage', true);
    }

    // Methods
    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    public function canFollow(User $user): bool
    {
        if ($this->id === $user->id)
            return false;
        if ($user->is_private && !$this->isFollowing($user))
            return false;
        return true;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    public function markAsUnderage(): void
    {
        $this->update(['is_underage' => true]);
    }


    public function isVerified(): bool
    {
        return ($this->email_verified_at || $this->phone_verified_at)
            && $this->status === 'active'
            && !$this->is_banned;
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->email_verified_at || $this->phone_verified_at)
            && !$this->is_banned;
    }

    public static function calculateIsUnderage($birthDate): bool
    {
        if (!$birthDate) {
            return false;
        }

        $age = now()->diffInYears($birthDate);
        return $age < 18;
    }

    public function isAdmin(): bool
    {
        return $this->username === 'admin';
    }
}