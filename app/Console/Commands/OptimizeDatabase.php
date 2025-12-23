<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeDatabase extends Command
{
    protected $signature = 'db:optimize';
    protected $description = 'Optimize database for better performance';

    public function handle()
    {
        $this->info('🔧 Optimizing database...');

        // Optimize tables
        $tables = ['posts', 'users', 'likes', 'comments', 'follows'];
        
        foreach ($tables as $table) {
            $this->line("  Optimizing {$table}...");
            try {
                DB::statement("OPTIMIZE TABLE {$table}");
            } catch (\Exception $e) {
                $this->warn("  Warning: Could not optimize {$table}");
            }
        }

        // Update table statistics
        try {
            DB::statement('ANALYZE TABLE posts, users, likes, comments, follows');
            $this->line('  ✓ Table statistics updated');
        } catch (\Exception $e) {
            $this->warn('  Warning: Could not update statistics');
        }

        $this->info('✅ Database optimization completed');
    }
}