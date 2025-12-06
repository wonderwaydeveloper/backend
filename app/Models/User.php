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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        'is_private',
        'is_verified',
        'is_banned',
        'two_factor_enabled',
        'two_factor_secret',
        'provider',
        'provider_id',
        'is_underage',
        'email_verified_at',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
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
            'two_factor_enabled' => 'boolean',
            'last_login_at' => 'datetime',
            'followers_count' => 'integer',
            'following_count' => 'integer',
            'posts_count' => 'integer',
        ];
    }

    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
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
        return $query->where('is_banned', false);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
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

    public function enableTwoFactor(): void
    {
        $this->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => Str::random(32), // تولید یک رشته تصادفی به عنوان رمز
        ]);
        $this->update(['two_factor_enabled' => true]);
    }

    public function disableTwoFactor(): void
    {
        $this->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }


    public function isAdmin(): bool
    {
        // منطق تشخیص ادمین را اینجا قرار دهید
        return $this->username === 'admin' || $this->role === 'admin'; // مثال
    }

}