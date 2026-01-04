<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'device_name',
        'active',
        'last_used_at',
        'browser',
        'os',
        'push_token',
        'ip_address',
        'user_agent',
        'fingerprint',
        'is_trusted'
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_used_at' => 'datetime',
        'is_trusted' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
