<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'location',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeLoginAttempts($query)
    {
        return $query->where('action', 'like', '%login%');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Methods
    public static function logSecurityEvent(User $user, string $action, array $metadata = []): void
    {
        self::create([
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'location' => self::getLocationFromIP(request()->ip()),
            'metadata' => $metadata,
        ]);
    }

    private static function getLocationFromIP(string $ip): string
    {
        // استفاده از سرویس IP geolocation
        // این یک نمونه ساده است
        try {
            // می‌توانید از APIهایی مانند ipapi.co استفاده کنید
            return 'Tehran, Iran'; // مقدار نمونه
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
}