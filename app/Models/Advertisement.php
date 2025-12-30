<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'image_url',
        'target_audience',
        'budget',
        'clicks',
        'impressions',
        'status',
        'start_date',
        'end_date',
        'advertiser_id'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'target_audience' => 'array'
    ];

    public function advertiser()
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }
}
