<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'avatar' => $this->avatar,
            'banner' => $this->banner,
            'privacy' => $this->privacy,
            'rules' => $this->rules,
            'settings' => $this->settings,
            'member_count' => $this->member_count,
            'post_count' => $this->post_count,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at,
            'creator' => $this->whenLoaded('creator', function() {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'username' => $this->creator->username,
                    'avatar' => $this->creator->avatar,
                ];
            }),
            'user_role' => $this->when(auth()->check(), function() {
                return $this->getUserRole(auth()->user());
            }),
            'is_member' => $this->when(auth()->check(), function() {
                return !$this->canUserJoin(auth()->user());
            }),
            'can_post' => $this->when(auth()->check(), function() {
                return $this->canUserPost(auth()->user());
            }),
            'members' => $this->whenLoaded('members', function() {
                return $this->members->take(5)->map(function($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'username' => $member->username,
                        'avatar' => $member->avatar,
                        'role' => $member->pivot->role,
                    ];
                });
            }),
        ];
    }
}