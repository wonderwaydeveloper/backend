<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    protected $fillable = [
        'group_conversation_id',
        'user_id',
        'is_admin',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];
}
