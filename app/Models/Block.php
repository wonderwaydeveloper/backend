<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Block extends Model
{
    protected $fillable = [
        'blocker_id',
        'blocked_id',
        'reason',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($block) {
            // Sanitize reason field to prevent XSS
            if ($block->reason) {
                $block->reason = strip_tags($block->reason);
            }
        });
        
        static::updating(function ($block) {
            // Sanitize reason field to prevent XSS
            if ($block->reason) {
                $block->reason = strip_tags($block->reason);
            }
        });
    }

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }
}
