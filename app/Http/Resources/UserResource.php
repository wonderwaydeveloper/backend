<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name ?? $this->name,
            'username' => $this->username,
            'email' => $this->when($this->isCurrentUser(), $this->email),
            'bio' => $this->bio ?? '',
            'avatar' => $this->avatar ?? null,
            'cover' => $this->cover ?? null,
            'profile_link_color' => $this->profile_link_color ?? '#1DA1F2',
            'profile_text_color' => $this->profile_text_color ?? '#14171A',
            'location' => $this->location ?? '',
            'website' => $this->website ?? '',
            'verified' => (bool) $this->verified,
            'verification_type' => $this->verification_type ?? 'none',
            'verification_badge' => $this->getVerificationBadge(),
            'verified_at' => $this->verified_at,
            'is_premium' => (bool) $this->is_premium,
            'is_private' => (bool) $this->is_private,
            
            'is_online' => (bool) $this->is_online,
            'last_seen_at' => $this->last_seen_at,
            'created_at' => $this->created_at,
            'pinned_post_id' => $this->pinned_post_id,
          
            // Privacy settings (only for current user)
            'allow_dms_from' => $this->when($this->isCurrentUser(), $this->allow_dms_from ?? 'everyone'),
            'quality_filter' => $this->when($this->isCurrentUser(), (bool) $this->quality_filter),
            'allow_sensitive_media' => $this->when($this->isCurrentUser(), (bool) $this->allow_sensitive_media),
            
            // Counts
            'posts_count' => $this->posts_count ? (int) $this->posts_count : 0,
            'followers_count' => $this->followers_count ? (int) $this->followers_count : 0,
            'following_count' => $this->following_count ? (int) $this->following_count : 0,
            'favourites_count' => $this->favourites_count ? (int) $this->favourites_count : 0,
            'listed_count' => $this->listed_count ? (int) $this->listed_count : 0,
            
            // Twitter aliases
            'protected_posts' => (bool) $this->is_private,
            'profile_banner_url' => $this->cover,
            
            // Computed fields
            'is_following' => $this->when(
                auth()->check() && !$this->isCurrentUser(),
                fn() => auth()->user()->isFollowing($this->id)
            ),
        ];
    }
    
    private function isCurrentUser(): bool
    {
        return auth()->check() && auth()->id() === $this->id;
    }
}