<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'name',
        'type',
        'max_participants',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('role', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    public function activeParticipants()
    {
        return $this->participants()->whereNull('conversation_participants.left_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function getOtherUser($userId)
    {
        if ($this->isGroup()) {
            return null;
        }
        return $this->user_one_id === $userId ? $this->userTwo : $this->userOne;
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    public function canAddParticipant(): bool
    {
        return $this->activeParticipants()->count() < $this->max_participants;
    }

    public function hasParticipant(int $userId): bool
    {
        return $this->activeParticipants()->where('user_id', $userId)->exists();
    }

    public static function between($userOneId, $userTwoId)
    {
        return self::where(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userOneId)
                  ->where('user_two_id', $userTwoId);
        })->orWhere(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userTwoId)
                  ->where('user_two_id', $userOneId);
        })->first();
    }
}
