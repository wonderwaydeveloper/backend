<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'type',
        'metadata',
        'reply_to',
        'edited_at',
        'deleted_at',
        'seen_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
        'seen_at' => 'datetime',
    ];

    // Relationships
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(PrivateMessage::class, 'reply_to');
    }

    public function media()
    {
        return $this->hasMany(MessageMedia::class);
    }

    // Scopes
    public function scopeUnseen($query)
    {
        return $query->whereNull('seen_at');
    }

    public function scopeVisible($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Methods
    public function isEdited(): bool
    {
        return !is_null($this->edited_at);
    }

    public function isDeleted(): bool
    {
        return !is_null($this->deleted_at);
    }

    public function isSeen(): bool
    {
        return !is_null($this->seen_at);
    }

    public function markAsSeen(): void
    {
        if (!$this->isSeen()) {
            $this->update(['seen_at' => now()]);
        }
    }

    public function softDelete(): void
    {
        $this->update(['deleted_at' => now()]);
    }

    public function getContentForDisplay(): string
    {
        if ($this->isDeleted()) {
            return 'این پیام حذف شده است';
        }

        return $this->content ?? $this->getMediaPreview();
    }

    private function getMediaPreview(): string
    {
        return match($this->type) {
            'image' => '📷 تصویر',
            'video' => '🎥 ویدیو',
            'file' => '📎 فایل',
            'audio' => '🎵 صوت',
            default => '📄 محتوا'
        };
    }
}