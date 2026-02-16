<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpamDetectionService
{
    private $spamKeywords = [
        'spam', 'fake', 'scam', 'click here', 'free money', 'win now',
    ];

    private $suspiciousPatterns = [
        '/(.)\1{4,}/', // Repeated characters
        '/[A-Z]{5,}/', // Too many capitals
        '/\b\d{10,}\b/', // Long numbers (phone/card)
        '/https?:\/\/[^\s]+/', // URLs
    ];

    public function checkPost(Post $post): array
    {
        $score = 0;
        $reasons = [];

        // Content analysis
        $contentScore = $this->analyzeContent($post->content);
        $score += $contentScore['score'];
        $reasons = array_merge($reasons, $contentScore['reasons']);

        // User behavior analysis
        $userScore = $this->analyzeUserBehavior($post->user);
        $score += $userScore['score'];
        $reasons = array_merge($reasons, $userScore['reasons']);

        // Frequency analysis
        $frequencyScore = $this->analyzePostFrequency($post->user);
        $score += $frequencyScore['score'];
        $reasons = array_merge($reasons, $frequencyScore['reasons']);

        $isSpam = $score >= config('moderation.spam.thresholds.post');

        if ($isSpam) {
            $this->handleSpamDetection($post, $score, $reasons);
        }

        return [
            'is_spam' => $isSpam,
            'score' => $score,
            'reasons' => $reasons,
        ];
    }

    public function checkComment(Comment $comment): array
    {
        $score = 0;
        $reasons = [];

        // Content analysis
        $contentScore = $this->analyzeContent($comment->content);
        $score += $contentScore['score'];
        $reasons = array_merge($reasons, $contentScore['reasons']);

        // User behavior analysis
        $userScore = $this->analyzeUserBehavior($comment->user);
        $score += $userScore['score'];
        $reasons = array_merge($reasons, $userScore['reasons']);

        $isSpam = $score >= config('moderation.spam.thresholds.comment');

        if ($isSpam) {
            $this->handleSpamComment($comment, $score, $reasons);
        }

        return [
            'is_spam' => $isSpam,
            'score' => $score,
            'reasons' => $reasons,
        ];
    }

    private function analyzeContent(string $content): array
    {
        $score = 0;
        $reasons = [];

        // Check for spam keywords
        foreach ($this->spamKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $score += config('moderation.spam.penalties.spam_keyword');
                $reasons[] = "Contains spam keyword: {$keyword}";
            }
        }

        // Check for multiple URLs (more strict)
        $urlCount = preg_match_all('/https?:\/\/[^\s]+/', $content);
        if ($urlCount >= config('moderation.spam.limits.url_count_high')) {
            $score += config('moderation.spam.penalties.multiple_links_high');
            $reasons[] = "Too many links detected ({$urlCount} links)";
        } elseif ($urlCount >= config('moderation.spam.limits.url_count_medium')) {
            $score += config('moderation.spam.penalties.multiple_links_medium');
            $reasons[] = "Multiple links detected";
        } elseif ($urlCount >= 1) {
            $score += config('moderation.spam.penalties.single_link');
            $reasons[] = "Contains URL";
        }

        // Check other suspicious patterns
        foreach ($this->suspiciousPatterns as $pattern) {
            if ($pattern !== '/https?:\/\/[^\s]+/' && preg_match($pattern, $content)) {
                $score += config('moderation.spam.penalties.suspicious_pattern');
                $reasons[] = "Matches suspicious pattern";
            }
        }

        // Check content length
        if (strlen($content) < config('moderation.spam.limits.min_content_length')) {
            $score += config('moderation.spam.penalties.short_content');
            $reasons[] = "Content too short";
        }

        // Check for excessive emojis
        $emojiCount = preg_match_all('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]/u', $content);
        if ($emojiCount > config('moderation.spam.limits.max_emoji_count')) {
            $score += config('moderation.spam.penalties.excessive_emoji');
            $reasons[] = "Excessive emoji usage";
        }

        return ['score' => $score, 'reasons' => $reasons];
    }

    private function analyzeUserBehavior(User $user): array
    {
        $score = 0;
        $reasons = [];

        // New user check - handle null created_at
        if ($user->created_at && $user->created_at->diffInDays(now()) < config('moderation.spam.limits.new_user_days')) {
            $score += config('moderation.spam.penalties.new_account');
            $reasons[] = "Very new user account";
        }

        // Check user reputation
        $reportCount = \DB::table('reports')->where('reportable_type', 'user')
            ->where('reportable_id', $user->id)->count();
        if ($reportCount > config('moderation.spam.limits.report_threshold')) {
            $score += config('moderation.spam.penalties.multiple_reports');
            $reasons[] = "User has multiple reports";
        }

        // Check if user is already flagged
        if (isset($user->is_flagged) && $user->is_flagged) {
            $score += config('moderation.spam.penalties.flagged_user');
            $reasons[] = "User is flagged";
        }

        // Check follower ratio
        $followers = $user->followers()->count();
        $following = $user->following()->count();

        if ($following > config('moderation.spam.limits.following_threshold') && $followers < config('moderation.spam.limits.follower_threshold')) {
            $score += config('moderation.spam.penalties.suspicious_follower_ratio');
            $reasons[] = "Suspicious follower ratio";
        }

        return ['score' => $score, 'reasons' => $reasons];
    }

    private function analyzePostFrequency(User $user): array
    {
        $score = 0;
        $reasons = [];

        // Check posts in last hour
        $recentPosts = $user->posts()
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentPosts > config('moderation.spam.limits.posts_per_hour_high')) {
            $score += config('moderation.spam.penalties.high_frequency');
            $reasons[] = "Too many posts in short time";
        } elseif ($recentPosts > config('moderation.spam.limits.posts_per_hour_medium')) {
            $score += config('moderation.spam.penalties.medium_frequency');
            $reasons[] = "High posting frequency";
        }

        // Check duplicate content
        $lastPost = $user->posts()->latest()->first();
        if ($lastPost) {
            $similarPosts = $user->posts()
                ->where('content', 'like', '%' . substr($lastPost->content, 0, 50) . '%')
                ->where('id', '!=', $lastPost->id)
                ->count();

            if ($similarPosts > 0) {
                $score += config('moderation.spam.penalties.duplicate_content');
                $reasons[] = "Duplicate or similar content detected";
            }
        }

        return ['score' => $score, 'reasons' => $reasons];
    }

    private function handleSpamDetection(Post $post, int $score, array $reasons): void
    {
        try {
            // Create auto-report via Moderation System
            \App\Models\Report::create([
                'reporter_id' => null, // System-generated
                'reportable_type' => 'App\\Models\\Post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
                'description' => 'Auto-detected spam content',
                'status' => 'pending',
                'auto_detected' => true,
                'spam_score' => $score,
                'detection_reasons' => $reasons,
            ]);

            Log::info('Spam detected and reported', [
                'post_id' => $post->id,
                'user_id' => $post->user_id,
                'score' => $score,
                'reasons' => $reasons,
            ]);

            // Update user spam score for tracking
            $this->updateUserSpamScore($post->user, $score);

        } catch (\Exception $e) {
            Log::error('Error handling spam detection', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleSpamComment(Comment $comment, int $score, array $reasons): void
    {
        try {
            // Create auto-report via Moderation System
            \App\Models\Report::create([
                'reporter_id' => null, // System-generated
                'reportable_type' => 'App\\Models\\Comment',
                'reportable_id' => $comment->id,
                'reason' => 'spam',
                'description' => 'Auto-detected spam content',
                'status' => 'pending',
                'auto_detected' => true,
                'spam_score' => $score,
                'detection_reasons' => $reasons,
            ]);

            Log::info('Spam comment detected and reported', [
                'comment_id' => $comment->id,
                'user_id' => $comment->user_id,
                'score' => $score,
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling spam comment', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function updateUserSpamScore(User $user, int $spamScore): void
    {
        $cacheKey = "user_spam_score_{$user->id}";
        $currentScore = Cache::get($cacheKey, 0);
        $newScore = $currentScore + ($spamScore / 10);

        Cache::put($cacheKey, $newScore, now()->addDays(7));

        // Create user report if spam score is too high (let Moderation handle suspension)
        if ($newScore >= config('moderation.spam.thresholds.user')) {
            \App\Models\Report::create([
                'reporter_id' => null, // System-generated
                'reportable_type' => 'App\\Models\\User',
                'reportable_id' => $user->id,
                'reason' => 'spam',
                'description' => "User has high spam score: {$newScore}",
                'status' => 'pending',
                'auto_detected' => true,
                'spam_score' => (int)$newScore,
                'detection_reasons' => ['High cumulative spam score'],
            ]);

            Log::warning('User reported for high spam score', [
                'user_id' => $user->id,
                'spam_score' => $newScore,
            ]);
        }
    }

    public function getUserSpamScore(User $user): int
    {
        return Cache::get("user_spam_score_{$user->id}", 0);
    }

    public function isUserSuspicious(User $user): bool
    {
        return $this->getUserSpamScore($user) >= 30;
    }
}
