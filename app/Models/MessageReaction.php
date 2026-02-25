<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReaction extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'emoji',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function allowedEmojis(): array
    {
        return ['❤️', '😂', '👍', '😮', '😢', '🙏'];
    }

    public static function isValidEmoji(string $emoji): bool
    {
        return in_array($emoji, self::allowedEmojis());
    }
}
