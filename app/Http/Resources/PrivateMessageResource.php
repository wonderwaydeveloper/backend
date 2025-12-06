<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivateMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->getContentForDisplay(),
            'type' => $this->type,
            'is_edited' => $this->isEdited(),
            'is_deleted' => $this->isDeleted(),
            'is_seen' => $this->isSeen(),
            'seen_at' => $this->seen_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'edited_at' => $this->edited_at?->toISOString(),

            // فرستنده
            'user' => new UserResource($this->whenLoaded('user')),

            // مدیا
            'media' => MessageMediaResource::collection($this->whenLoaded('media')),

            // پاسخ به
            'reply_to' => new PrivateMessageResource($this->whenLoaded('replyTo')),

            // اطلاعات مکالمه
            'conversation_id' => $this->conversation_id,
        ];
    }

    /**
     * داده‌های اضافی
     */

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'can_reply' => $request->user() && $request->user()->can('sendMessage', $this->conversation),
                'can_delete' => $request->user() && (
                    $request->user()->id === $this->user_id ||
                    $request->user()->isAdmin()
                ),
            ],
        ];
    }
}