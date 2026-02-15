<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ABTestEvent extends Model
{
    use HasFactory;
    
    protected $table = 'ab_test_events';
    
    protected $fillable = [
        'ab_test_id',
        'user_id',
        'variant',
        'event_type',
        'event_data'
    ];

    protected $casts = [
        'event_data' => 'array'
    ];

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(ABTest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
