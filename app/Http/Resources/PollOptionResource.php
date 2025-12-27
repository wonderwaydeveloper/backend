<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'vote_count' => $this->vote_count ?? 0,
            'percentage' => $this->when($this->poll->total_votes > 0, function() {
                return round(($this->vote_count / $this->poll->total_votes) * 100, 1);
            }, 0)
        ];
    }
}