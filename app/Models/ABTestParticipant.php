<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ABTestParticipant extends Model
{
    use HasFactory;
    
    protected $table = 'ab_test_participants';
    
    protected $fillable = [
        'ab_test_id',
        'user_id',
        'variant',
        'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
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
