<?php

namespace App\Actions\Notification;

use App\DTOs\NotificationDTO;
use App\Models\Notification;

class SendNotificationAction
{
    public function execute(NotificationDTO $dto): Notification
    {
        return Notification::create([
            'user_id' => $dto->userId,
            'type' => $dto->type,
            'title' => $dto->title,
            'message' => $dto->message,
            'data' => $dto->data,
            'read_at' => null,
        ]);
    }
}