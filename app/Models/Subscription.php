<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan',
        'status',
        'amount',
        'starts_at',
        'ends_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive()
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public static function plans()
    {
        return [
            'basic' => [
                'name' => 'Free',
                'price' => 0,
                'features' => [
                    '10 posts per day',
                    '5 images per post',
                    'Contains ads',
                ],
            ],
            'premium' => [
                'name' => 'Premium',
                'price' => 4.99,
                'features' => [
                    'Unlimited posts',
                    'No ads',
                    'Edit posts',
                    'Upload videos up to 10 minutes',
                    'Premium badge',
                ],
            ],
            'creator' => [
                'name' => 'Content Creator',
                'price' => 9.99,
                'features' => [
                    'All Premium features',
                    'Advanced analytics',
                    'Monetize content',
                    'Live streaming',
                    'API access',
                ],
            ],
        ];
    }
}
