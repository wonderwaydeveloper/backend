<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentalControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'child_id',
        'restrictions',
        'allowed_features',
        'daily_limit_start',
        'daily_limit_end',
        'max_daily_usage',
        'is_active',
    ];

    protected $casts = [
        'restrictions' => 'array',
        'allowed_features' => 'array',
        'daily_limit_start' => 'string', // تغییر از datetime به string
        'daily_limit_end' => 'string', // تغییر از datetime به string
        'max_daily_usage' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function child()
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    // Methods
   public function isWithinTimeLimit(): bool
    {
        if (empty($this->daily_limit_start) || empty($this->daily_limit_end)) {
            return true;
        }

        $now = now();
        $currentTime = $now->format('H:i:s');
        
        // تبدیل string به time
        $start = $this->daily_limit_start;
        $end = $this->daily_limit_end;
        
        // اگر start و end به صورت زمان خالص هستند
        return $currentTime >= $start && $currentTime <= $end;
    }

    public function getRemainingUsageToday(): int
    {
        // اینجا باید لاگ استفاده روزانه را چک کنید
        $todayUsage = 0; // از مدل لاگ محاسبه شود
        return max(0, $this->max_daily_usage - $todayUsage);
    }

    public function isFeatureAllowed(string $feature): bool
    {
        if (empty($this->allowed_features)) {
            return true;
        }

        return in_array($feature, $this->allowed_features);
    }

    public function enable(): void
    {
        $this->update(['is_active' => true]);
    }

    public function disable(): void
    {
        $this->update(['is_active' => false]);
    }
}