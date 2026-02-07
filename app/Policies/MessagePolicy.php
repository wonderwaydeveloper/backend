<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function send(User $sender, User $recipient): bool
    {
        // Can't send to yourself
        if ($sender->id === $recipient->id) {
            return false;
        }

        // Can't send if blocked
        if (in_array($sender->id, $recipient->blocked_users ?? [])) {
            return false;
        }

        // Check DM settings (assuming it's in notification_preferences)
        $dmSettings = $recipient->notification_preferences['dm_settings'] ?? 'everyone';

        if ($dmSettings === 'none') {
            return false;
        }

        if ($dmSettings === 'followers') {
            return $recipient->isFollowing($sender->id);
        }

        return true; // everyone
    }

    public function view(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id || $user->id === $message->recipient_id;
    }

    public function delete(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id || $user->id === $message->recipient_id;
    }
}
