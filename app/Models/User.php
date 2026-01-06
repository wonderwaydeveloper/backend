<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasRoles;

    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'bio',
        'avatar',
        'cover',
        'location',
        'website',
        'verified',
        'date_of_birth',
        'is_child',
        'subscription_plan',
        'is_premium',
        'is_private',
        'google_id',
        'apple_id',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_backup_codes',
        'is_online',
        'last_seen_at',
        'last_active_at',
        'email_notifications_enabled',
        'email_verification_token',
        'refresh_token',
        'password_changed_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_backup_codes',
        'email_verification_token',
        'refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'is_child' => 'boolean',
            'is_premium' => 'boolean',
            'is_private' => 'boolean',
            'verified' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'is_online' => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'is_flagged' => 'boolean',
            'is_suspended' => 'boolean',
            'is_banned' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

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
            ->withTimestamps()
            ->select(['users.id', 'name', 'username', 'avatar']);
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps()
            ->select(['users.id', 'name', 'username', 'avatar']);
    }

    public function isFollowing($userId)
    {
        return $this->following()->where('following_id', $userId)->exists();
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeWithCounts($query)
    {
        return $query->withCount(['posts', 'followers', 'following']);
    }

    public function scopePopular($query)
    {
        return $query->withCount('followers')
            ->orderBy('followers_count', 'desc');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }



    public function getAgeAttribute()
    {
        return $this->date_of_birth?->age;
    }



    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('ends_at', '>', now());
    }

    public function isPremium()
    {
        return $this->is_premium && $this->activeSubscription()->exists();
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function reposts()
    {
        return $this->hasMany(Repost::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    public function followRequests()
    {
        return $this->hasMany(FollowRequest::class, 'following_id');
    }

    public function sentFollowRequests()
    {
        return $this->hasMany(FollowRequest::class, 'follower_id');
    }

    public function scheduledPosts()
    {
        return $this->hasMany(ScheduledPost::class);
    }

    public function devices()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function reports()
    {
        return $this->hasMany(\App\Models\Report::class);
    }

    public function mentions()
    {
        return $this->hasMany(Mention::class);
    }

    public function moments()
    {
        return $this->hasMany(Moment::class);
    }

    public function conversionMetrics()
    {
        return $this->hasMany(ConversionMetric::class);
    }

    public function creatorFunds()
    {
        return $this->hasMany(\App\Monetization\Models\CreatorFund::class, 'creator_id');
    }

    public function communities()
    {
        return $this->belongsToMany(Community::class, 'community_members')
                    ->withPivot('role', 'joined_at', 'permissions')
                    ->withTimestamps();
    }

    public function ownedCommunities()
    {
        return $this->hasMany(Community::class, 'created_by');
    }

    public function communityJoinRequests()
    {
        return $this->hasMany(CommunityJoinRequest::class);
    }

    /**
     * Check if user can access Filament admin panel
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->hasRole('admin');
    }
}
