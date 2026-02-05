<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class CleanupAuditLogsCommand extends Command
{
    protected $signature = 'audit:cleanup {--days=90 : Number of days to keep} {--dry-run : Show what would be deleted}';
    protected $description = 'Clean up old audit logs to prevent database bloat';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');
        
        $this->info("Cleaning up audit logs older than {$days} days...");
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }
        
        $cutoffDate = now()->subDays($days);
        
        $query = AuditLog::where('timestamp', '<', $cutoffDate);
        
        // Always preserve high-risk and security events for longer
        $preserveQuery = clone $query;
        $preserveCount = $preserveQuery->where(function ($q) {
            $q->where('risk_level', 'high')
              ->orWhere('action', 'like', 'security.%');
        })->count();
        
        // Only delete low and medium risk non-security events
        $deleteQuery = clone $query;
        $deleteQuery->where('risk_level', '!=', 'high')
                   ->where('action', 'not like', 'security.%');
        
        $deleteCount = $deleteQuery->count();
        
        if ($isDryRun) {
            $this->table(
                ['Category', 'Count', 'Action'],
                [
                    ['Records to delete', $deleteCount, 'Would be deleted'],
                    ['High-risk/Security records', $preserveCount, 'Would be preserved'],
                    ['Total old records', $deleteCount + $preserveCount, 'Found']
                ]
            );
        } else {
            $deleted = $deleteQuery->delete();
            
            $this->table(
                ['Category', 'Count'],
                [
                    ['Records deleted', $deleted],
                    ['High-risk/Security preserved', $preserveCount],
                    ['Total processed', $deleted + $preserveCount]
                ]
            );
            
            $this->info("Cleanup completed. Deleted {$deleted} audit log records.");
        }
        
        return Command::SUCCESS;
    }
}