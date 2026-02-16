<?php

namespace App\Monetization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'month',
        'year',
        'total_views',
        'total_engagement',
        'quality_score',
        'earnings',
        'status',
        'paid_at',
        'metrics',
    ];

    protected $casts = [
        'earnings' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'paid_at' => 'datetime',
        'metrics' => 'array',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\Monetization\Models\CreatorFundFactory::new();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function calculateEarnings(): float
    {
        if ($this->total_views == 0) {
            return 0;
        }

        $baseRate = config('monetization.creator_fund.base_rate');
        $engagementMultiplier = min($this->total_engagement / $this->total_views, config('monetization.creator_fund.max_engagement_multiplier'));
        $qualityMultiplier = $this->quality_score / 100;

        return $this->total_views * $baseRate * (1 + $engagementMultiplier) * $qualityMultiplier;
    }

    public function isEligible(): bool
    {
        return $this->total_views >= config('monetization.creator_fund.min_views')
            && $this->quality_score >= config('monetization.creator_fund.min_quality_score')
            && $this->creator->followers()->count() >= config('monetization.creator_fund.min_followers');
    }
}
