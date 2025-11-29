<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'token',
        'refresh_token',
        'expires_at',
        'profile',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'profile' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function updateToken(array $tokenData): void
    {
        $this->update([
            'token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? $this->refresh_token,
            'expires_at' => isset($tokenData['expires_in']) ? 
                now()->addSeconds($tokenData['expires_in']) : $this->expires_at,
        ]);
    }
}