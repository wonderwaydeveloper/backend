<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_id',
        'invited_by',
        'invite_code',
        'max_uses',
        'uses',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isValid(): bool
    {
        // Check if expired
        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        // Check if max uses reached
        if ($this->uses >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
