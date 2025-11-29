<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'created_by',
        'metadata',
        'last_message_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
                    ->withPivot(['joined_at', 'left_at', 'role', 'settings'])
                    ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages()
    {
        return $this->hasMany(PrivateMessage::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(PrivateMessage::class)->latest();
    }

    public function activeUsers()
    {
        return $this->users()->wherePivotNull('left_at');
    }

    // Scopes
    public function scopeDirect($query)
    {
        return $query->where('type', 'direct');
    }

    public function scopeGroup($query)
    {
        return $query->where('type', 'group');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('users', function ($q) use ($userId) {
            $q->where('user_id', $userId)->whereNull('left_at');
        });
    }

    // Methods
    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function addParticipant(User $user, string $role = 'member'): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => [
                'joined_at' => now(),
                'role' => $role,
                'settings' => json_encode(['notifications' => true])
            ]
        ]);
    }

    public function removeParticipant(User $user): void
    {
        $this->users()->updateExistingPivot($user->id, ['left_at' => now()]);
    }

    public function isParticipant(User $user): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();
    }

    public function updateLastMessage(): void
    {
        $this->update(['last_message_at' => now()]);
    }
}