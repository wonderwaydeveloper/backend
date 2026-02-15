<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DeviceToken extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'device_name',
        'active',
        'last_used_at',
        'browser',
        'os',
        'push_token',
        'ip_address',
        'user_agent',
        'fingerprint',
        'is_trusted'
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_used_at' => 'datetime',
        'is_trusted' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('active', false)
                    ->orWhere('last_used_at', '<', now()->subDays(config('authentication.device.max_inactivity_days', 30)));
    }

    public function scopeTrusted(Builder $query): Builder
    {
        return $query->where('is_trusted', true);
    }

    public function scopeRecentlyUsed(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_used_at', '>', now()->subDays($days));
    }

    /**
     * Mark device as inactive
     */
    public function markInactive(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check if device is stale (not used for configured days)
     */
    public function isStale(): bool
    {
        return $this->last_used_at < now()->subDays(config('authentication.device.max_inactivity_days', 30));
    }
}
