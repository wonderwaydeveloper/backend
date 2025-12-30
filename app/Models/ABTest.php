<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ABTest extends Model
{
    protected $table = 'ab_tests';
    
    protected $fillable = [
        'name',
        'description',
        'status',
        'traffic_percentage',
        'variants',
        'targeting_rules',
        'starts_at',
        'ends_at'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'variants' => 'array',
        'targeting_rules' => 'array'
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(ABTestParticipant::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ABTestEvent::class);
    }
}
