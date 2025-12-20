<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentalControl extends Model
{
    protected $fillable = [
        'child_id',
        'require_follow_approval',
        'restrict_dm',
        'content_filter',
        'daily_post_limit',
        'usage_start_time',
        'usage_end_time',
    ];

    protected $casts = [
        'require_follow_approval' => 'boolean',
        'restrict_dm' => 'boolean',
        'content_filter' => 'boolean',
        'daily_post_limit' => 'integer',
    ];

    public function child()
    {
        return $this->belongsTo(User::class, 'child_id');
    }
}
