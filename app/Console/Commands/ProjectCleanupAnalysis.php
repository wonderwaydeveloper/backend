<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProjectCleanupAnalysis extends Command
{
    protected $signature = 'project:cleanup-analysis';
    protected $description = 'Analyze project for unnecessary files and folders';

    public function handle()
    {
        $this->info('🧹 Analyzing project for cleanup opportunities...');
        
        $this->checkUnnecessaryFiles();
        $this->checkEmptyDirectories();
        $this->checkDuplicateCode();
        $this->checkUnusedAssets();
        $this->checkTempFiles();
        
        $this->info('✅ Cleanup analysis completed');
    }

    private function checkUnnecessaryFiles()
    {
        $this->info('📁 Checking unnecessary files:');
        
        $unnecessaryFiles = [
            // Development files
            '.php-cs-fixer.cache' => 'PHP CS Fixer cache',
            '.phpunit.result.cache' => 'PHPUnit cache',
            'composer.lock' => 'Keep for production consistency',
            'package-lock.json' => 'Keep for frontend consistency',
            
            // IDE files
            '.vscode/' => 'VS Code settings',
            '.idea/' => 'PhpStorm settings',
            '*.swp' => 'Vim swap files',
            '*.swo' => 'Vim swap files',
            
            // OS files
            '.DS_Store' => 'macOS system files',
            'Thumbs.db' => 'Windows thumbnails',
            'desktop.ini' => 'Windows folder settings',
            
            // Logs
            'storage/logs/*.log' => 'Old log files',
            'npm-debug.log*' => 'NPM debug logs',
            'yarn-debug.log*' => 'Yarn debug logs',
            
            // Build artifacts
            'node_modules/' => 'NPM dependencies (regeneratable)',
            'vendor/' => 'Composer dependencies (regeneratable)',
            'public/build/' => 'Compiled assets',
            'public/hot' => 'Vite hot reload file',
            
            // Test artifacts
            'coverage/' => 'Test coverage reports',
            '.nyc_output/' => 'Coverage temp files',
        ];

        foreach ($unnecessaryFiles as $pattern => $description) {
            if (File::exists(base_path($pattern))) {
                $this->warn("  ⚠️  {$pattern} - {$description}");
            }
        }
    }

    private function checkEmptyDirectories()
    {
        $this->info('📂 Checking empty directories:');
        
        $directories = [
            'storage/app/private',
            'storage/app/secrets', 
            'storage/recordings',
            'storage/streams',
            'public/storage',
        ];

        foreach ($directories as $dir) {
            $fullPath = base_path($dir);
            if (File::exists($fullPath) && $this->isEmptyDirectory($fullPath)) {
                $this->line("  📂 {$dir} - Empty directory");
            }
        }
    }

    private function checkDuplicateCode()
    {
        $this->info('🔍 Checking for potential duplicates:');
        
        // Check for duplicate controllers
        $controllers = File::files(app_path('Http/Controllers/Api'));
        $controllerNames = [];
        
        foreach ($controllers as $controller) {
            $name = $controller->getFilenameWithoutExtension();
            if (str_contains($name, 'Performance')) {
                $controllerNames[] = $name;
            }
        }
        
        if (count($controllerNames) > 1) {
            $this->warn('  ⚠️  Multiple Performance controllers found: ' . implode(', ', $controllerNames));
        }

        // Check for similar services
        $services = File::files(app_path('Services'));
        $serviceNames = [];
        
        foreach ($services as $service) {
            $name = $service->getFilenameWithoutExtension();
            if (str_contains($name, 'Cache') || str_contains($name, 'Optimization')) {
                $serviceNames[] = $name;
            }
        }
        
        if (count($serviceNames) > 3) {
            $this->warn('  ⚠️  Many optimization services: ' . implode(', ', $serviceNames));
        }
    }

    private function checkUnusedAssets()
    {
        $this->info('🖼️  Checking unused assets:');
        
        $publicDirs = ['css', 'js', 'images', 'fonts'];
        
        foreach ($publicDirs as $dir) {
            $path = public_path($dir);
            if (File::exists($path)) {
                $files = File::files($path);
                if (empty($files)) {
                    $this->line("  📁 public/{$dir} - Empty asset directory");
                }
            }
        }
    }

    private function checkTempFiles()
    {
        $this->info('🗑️  Checking temporary files:');
        
        $tempPatterns = [
            'storage/framework/cache/data/*',
            'storage/framework/sessions/*', 
            'storage/framework/views/*',
            'bootstrap/cache/*.php',
        ];

        foreach ($tempPatterns as $pattern) {
            $files = glob(base_path($pattern));
            if (!empty($files)) {
                $count = count($files);
                $this->line("  🗑️  {$pattern} - {$count} temp files");
            }
        }
    }

    private function isEmptyDirectory(string $path): bool
    {
        if (!is_dir($path)) return false;
        
        $files = scandir($path);
        return count($files) <= 2; // Only . and ..
    }
}