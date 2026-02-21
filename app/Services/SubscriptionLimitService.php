<?php

namespace App\Services;

use App\Models\User;

class SubscriptionLimitService
{
    public function getUserLimits(User $user): array
    {
        $role = $this->getUserHighestRole($user);
        return config("monetization.roles.{$role}", config('monetization.roles.user'));
    }
    
    public function getUserHighestRole(User $user): string
    {
        // Priority order: admin > moderator > organization > premium > verified > user
        if ($user->hasRole('admin')) {
            return 'admin';
        }
        
        if ($user->hasRole('moderator')) {
            return 'moderator';
        }
        
        if ($user->hasRole('organization')) {
            return 'organization';
        }
        
        if ($user->hasRole('premium')) {
            return 'premium';
        }
        
        if ($user->hasRole('verified')) {
            return 'verified';
        }
        
        return 'user';
    }
    
    public function canUploadHD(User $user): bool
    {
        $limits = $this->getUserLimits($user);
        return $limits['hd_upload'] ?? false;
    }
    
    public function getMaxFileSize(User $user): int
    {
        $limits = $this->getUserLimits($user);
        return $limits['max_file_size_kb'] ?? 5120;
    }
    
    public function getMaxMediaPerPost(User $user): int
    {
        $limits = $this->getUserLimits($user);
        return $limits['media_per_post'] ?? 4;
    }
    
    public function getMaxVideoLength(User $user): int
    {
        $limits = $this->getUserLimits($user);
        return $limits['video_length_seconds'] ?? 140;
    }
    
    public function getScheduledPostsLimit(User $user): int
    {
        $limits = $this->getUserLimits($user);
        return $limits['scheduled_posts'] ?? 0;
    }
    
    public function canCreateAdvertisements(User $user): bool
    {
        $limits = $this->getUserLimits($user);
        return $limits['advertisements'] ?? false;
    }
    
    public function getRateLimit(User $user): int
    {
        $limits = $this->getUserLimits($user);
        return $limits['rate_limit_per_minute'] ?? 60;
    }
    
    public function getPostsPerDayLimit(User $user): int
    {
        $limits = $this->getUserLimits($user);
        return $limits['posts_per_day'] ?? 100;
    }
}
