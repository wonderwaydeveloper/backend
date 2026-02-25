<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationSetting extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'is_muted',
        'is_archived',
        'is_pinned',
        'muted_until',
    ];

    protected $casts = [
        'is_muted' => 'boolean',
        'is_archived' => 'boolean',
        'is_pinned' => 'boolean',
        'muted_until' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isMuted(): bool
    {
        if (!$this->is_muted) {
            return false;
        }

        if ($this->muted_until && $this->muted_until->isPast()) {
            $this->update(['is_muted' => false, 'muted_until' => null]);
            return false;
        }

        return true;
    }
}
