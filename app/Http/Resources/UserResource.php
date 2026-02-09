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
            'display_name' => $this->getDisplayNameAttribute(),
            'username' => $this->username,
            'email' => $this->when($this->isCurrentUser(), $this->email),
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'cover' => $this->cover,
            'profile_banner_url' => $this->cover, // Alias for Twitter compatibility
            'profile_link_color' => $this->profile_link_color,
            'profile_text_color' => $this->profile_text_color,
            'location' => $this->location,
            'website' => $this->website,
            'verified' => $this->isVerified(),
            'verification_type' => $this->verification_type,
            'verification_badge' => $this->getVerificationBadge(),
            'verified_at' => $this->verified_at,
            'is_premium' => $this->is_premium,
            'is_private' => $this->is_private,
            'protected_tweets' => $this->is_private, // Alias for Twitter compatibility
            'is_online' => $this->is_online,
            'last_seen_at' => $this->last_seen_at,
            'created_at' => $this->created_at,
            'pinned_tweet_id' => $this->pinned_tweet_id,
            
            // Privacy settings (only for current user)
            'allow_dms_from' => $this->when($this->isCurrentUser(), $this->allow_dms_from),
            'quality_filter' => $this->when($this->isCurrentUser(), $this->quality_filter),
            'allow_sensitive_media' => $this->when($this->isCurrentUser(), $this->allow_sensitive_media),
            
            // Counts
            'posts_count' => $this->posts_count,
            'tweets_count' => $this->posts_count, // Alias for Twitter compatibility
            'followers_count' => $this->followers_count,
            'following_count' => $this->following_count,
            'favourites_count' => $this->favourites_count ?? 0,
            'listed_count' => $this->listed_count ?? 0,
            
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