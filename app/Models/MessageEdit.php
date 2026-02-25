<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageEdit extends Model
{
    protected $fillable = [
        'message_id',
        'old_content',
        'new_content',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
