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

    /**
     * The attributes that are NOT mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'email_verified_at',
        'phone_verified_at',
        'password_changed_at',
        'verified',
        'verification_type',
        'verified_at',
        'is_premium',
        'is_flagged',
        'is_suspended',
        'is_banned',
        'followers_count',
        'following_count',
        'posts_count',
        'favourites_count',
        'listed_count',
        'two_factor_enabled',
        'two_factor_secret',
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
            'verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'is_child' => 'boolean',
            'is_premium' => 'boolean',
            'is_private' => 'boolean', // Twitter's protected_tweets
            'quality_filter' => 'boolean',
            'allow_sensitive_media' => 'boolean',
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



    // Twitter-standard methods
    public function getDisplayNameAttribute()
    {
        return $this->attributes['display_name'] ?? $this->attributes['name'];
    }

    public function isVerified(): bool
    {
        return $this->verification_type && $this->verification_type !== 'none';
    }

    public function getVerificationBadge(): ?string
    {
        return match($this->verification_type) {
            'blue' => '✓',
            'gold' => '🏅',
            'gray' => '⚪',
            default => null
        };
    }

    public function isProtected(): bool
    {
        return $this->is_private; // Twitter's protected_tweets equivalent
    }

    // Alias methods for Twitter compatibility
    public function getTweetsCountAttribute()
    {
        return $this->posts_count; // Use existing posts_count
    }

    public function getProfileBannerUrlAttribute()
    {
        return $this->cover; // Use existing cover field
    }

    public function getProtectedTweetsAttribute()
    {
        return $this->is_private; // Use existing is_private field
    }

    public function pinnedPost()
    {
        return $this->belongsTo(Post::class, 'pinned_post_id');
    }



    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function premiumSubscriptions()
    {
        return $this->hasMany(PremiumSubscription::class);
    }

    public function activePremiumSubscription()
    {
        return $this->hasOne(PremiumSubscription::class)
            ->where('status', config('status.subscription.active'))
            ->where('ends_at', '>', now());
    }

    public function isPremium()
    {
        return $this->is_premium && $this->activePremiumSubscription()->exists();
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

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function moments()
    {
        return $this->hasMany(Moment::class);
    }

    public function conversionMetrics()
    {
        return $this->hasMany(ConversionMetric::class);
    }

    public function abTestParticipants()
    {
        return $this->hasMany(ABTestParticipant::class);
    }

    public function abTestEvents()
    {
        return $this->hasMany(ABTestEvent::class);
    }

    public function creatorFunds()
    {
        return $this->hasMany(\App\Monetization\Models\CreatorFund::class, 'creator_id');
    }

    public function advertisements()
    {
        return $this->hasMany(\App\Monetization\Models\Advertisement::class, 'advertiser_id');
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

    // Block relationships
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocker_id', 'blocked_id')
            ->withTimestamps();
    }

    public function blockedBy()
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocked_id', 'blocker_id')
            ->withTimestamps();
    }

    public function hasBlocked($userId): bool
    {
        return $this->blockedUsers()->where('blocked_id', $userId)->exists();
    }

    public function isBlockedBy($userId): bool
    {
        return $this->blockedBy()->where('blocker_id', $userId)->exists();
    }

    // Mute relationships
    public function mutedUsers()
    {
        return $this->belongsToMany(User::class, 'mutes', 'muter_id', 'muted_id')
            ->withPivot('expires_at')
            ->withTimestamps();
    }

    public function mutedBy()
    {
        return $this->belongsToMany(User::class, 'mutes', 'muted_id', 'muter_id')
            ->withPivot('expires_at')
            ->withTimestamps();
    }

    public function hasMuted($userId): bool
    {
        return $this->mutedUsers()
            ->where('users.id', $userId)
            ->where(function($q) {
                $q->whereNull('mutes.expires_at')
                  ->orWhere('mutes.expires_at', '>', now());
            })
            ->exists();
    }

    public function isMutedBy($userId): bool
    {
        return $this->mutedBy()
            ->where('users.id', $userId)
            ->where(function($q) {
                $q->whereNull('mutes.expires_at')
                  ->orWhere('mutes.expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if user can access Filament admin panel
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->hasRole('admin');
    }
}
