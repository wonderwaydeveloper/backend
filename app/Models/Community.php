<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Community extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'avatar',
        'banner',
        'privacy',
        'rules',
        'settings',
        'created_by',
        'is_verified',
    ];

    protected $guarded = [
        'id',
        'member_count',
        'post_count',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'rules' => 'array',
        'settings' => 'array',
        'is_verified' => 'boolean',
    ];

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strip_tags($value);
    }

    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = strip_tags($value);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($community) {
            if (empty($community->slug)) {
                $community->slug = Str::slug($community->name);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_members')
                    ->withPivot('role', 'joined_at', 'permissions')
                    ->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(CommunityJoinRequest::class);
    }

    public function moderators(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'moderator');
    }

    public function admins(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'admin');
    }

    public function canUserPost(User $user): bool
    {
        if ($this->privacy === 'public') {
            return true;
        }

        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function canUserJoin(User $user): bool
    {
        return !$this->members()->where('user_id', $user->id)->exists();
    }

    public function getUserRole(User $user): ?string
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->pivot->role;
    }

    public function canUserModerate(User $user): bool
    {
        $role = $this->getUserRole($user);
        return in_array($role, ['moderator', 'admin', 'owner']);
    }

    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        if (in_array($column, ['member_count', 'post_count'])) {
            $this->$column = max(0, $this->$column - $amount);
            $this->save();
            return $this;
        }
        return parent::decrement($column, $amount, $extra);
    }

    public function incrementMemberCount()
    {
        $this->increment('member_count');
    }

    public function decrementMemberCount()
    {
        $this->member_count = max(0, $this->member_count - 1);
        $this->save();
    }

    public function incrementPostCount()
    {
        $this->increment('post_count');
    }

    public function decrementPostCount()
    {
        $this->post_count = max(0, $this->post_count - 1);
        $this->save();
    }
}