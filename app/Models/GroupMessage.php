<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    protected $fillable = [
        'group_conversation_id',
        'sender_id',
        'content',
        'media_path',
        'media_type',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function group()
    {
        return $this->belongsTo(GroupConversation::class, 'group_conversation_id');
    }
}
