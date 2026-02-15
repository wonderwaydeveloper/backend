<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PremiumSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan',
        'price',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'status',
        'payment_method',
        'transaction_id',
        'features',
        'cancelled_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'features' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at > now();
    }

    public function isExpired(): bool
    {
        return $this->ends_at <= now();
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
