<?php

namespace App\Console\Commands;

use App\Services\TokenManagementService;
use Illuminate\Console\Command;

class CleanupTokensCommand extends Command
{
    protected $signature = 'auth:cleanup-tokens {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Clean up expired tokens and inactive sessions';

    public function handle(TokenManagementService $tokenService, \App\Services\SessionTimeoutService $timeoutService): int
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('Starting token cleanup process...');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }
        
        try {
            if (!$isDryRun) {
                $stats = $tokenService->cleanupExpiredTokens();
                
                $this->info('Cleanup completed successfully:');
                $this->table(
                    ['Type', 'Deleted Count'],
                    [
                        ['Access Tokens', $stats['access_tokens_deleted']],
                        ['Device Tokens', $stats['device_tokens_deleted']],
                        ['Password Reset Tokens', $stats['password_reset_tokens_deleted']],
                        ['Verification Tokens', $stats['verification_tokens_deleted']]
                    ]
                );
                
                $totalDeleted = array_sum($stats);
                $this->info("Total items cleaned up: {$totalDeleted}");
            } else {
                // Dry run - just show counts
                $this->showDryRunStats($timeoutService);
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Token cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function showDryRunStats(\App\Services\SessionTimeoutService $timeoutService = null): void
    {
        $timeoutService = $timeoutService ?? app(\App\Services\SessionTimeoutService::class);
        
        $expiredTokens = \Laravel\Sanctum\PersonalAccessToken::where('expires_at', '<', now())->count();
        $inactiveDevices = \App\Models\DeviceToken::where('last_used_at', '<', now()->subDays($timeoutService->getDeviceTokenInactivityLimit()))
            ->orWhere('active', false)->count();
        $expiredResets = \DB::table('password_reset_tokens')
            ->where('created_at', '<', now()->subMinutes($timeoutService->getPasswordResetExpiry()))->count();
        $expiredVerifications = \App\Models\User::whereNotNull('email_verification_token')
            ->whereNull('email_verified_at')
            ->where('updated_at', '<', now()->subHours($timeoutService->getEmailVerificationExpiry()))->count();
            
        $this->table(
            ['Type', 'Would Delete'],
            [
                ['Expired Access Tokens', $expiredTokens],
                ['Inactive Device Tokens', $inactiveDevices],
                ['Expired Password Reset Tokens', $expiredResets],
                ['Expired Verification Tokens', $expiredVerifications]
            ]
        );
        
        $total = $expiredTokens + $inactiveDevices + $expiredResets + $expiredVerifications;
        $this->info("Total items that would be cleaned up: {$total}");
    }
}