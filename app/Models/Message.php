<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Message extends Model
{
    use SoftDeletes, Searchable, HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message_type',
        'content',
        'read_at',
        'gif_url',
        'voice_duration',
        'forwarded_from_message_id',
        'edited_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'voice_duration' => 'integer',
        'edited_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function edits()
    {
        return $this->hasMany(MessageEdit::class);
    }

    public function forwardedFrom()
    {
        return $this->belongsTo(Message::class, 'forwarded_from_message_id');
    }

    public function reactionsSummary()
    {
        return $this->reactions()
            ->selectRaw('emoji, count(*) as count')
            ->groupBy('emoji');
    }

    public function hasReaction(int $userId, string $emoji): bool
    {
        return $this->reactions()
            ->where('user_id', $userId)
            ->where('emoji', $emoji)
            ->exists();
    }

    public function isVoice(): bool
    {
        return $this->message_type === 'voice';
    }

    public function isText(): bool
    {
        return $this->message_type === 'text';
    }

    public function isVideo(): bool
    {
        return $this->message_type === 'video';
    }

    public function isForwarded(): bool
    {
        return !is_null($this->forwarded_from_message_id);
    }

    public function isEdited(): bool
    {
        return !is_null($this->edited_at);
    }

    public function canEdit(): bool
    {
        return $this->created_at->diffInMinutes(now()) <= 15;
    }

    public function canDelete(): bool
    {
        return $this->created_at->diffInHours(now()) <= 48;
    }

    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // ==================== SCOUT SEARCHABLE ====================

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'sender_id' => $this->sender_id,
            'conversation_id' => $this->conversation_id,
            'message_type' => $this->message_type,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    public function searchableAs()
    {
        return 'messages_index';
    }

    public function shouldBeSearchable()
    {
        return !empty($this->content) && $this->message_type === 'text';
    }
}
