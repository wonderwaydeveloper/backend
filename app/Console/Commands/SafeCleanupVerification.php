<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SafeCleanupVerification extends Command
{
    protected $signature = 'cleanup:verify';
    protected $description = 'Verify files are safe to delete before cleanup';

    public function handle()
    {
        $this->info('🔍 Verifying safe cleanup...');
        
        $this->verifyControllers();
        $this->verifyServices();
        $this->verifyDirectories();
        $this->verifyConfigs();
        
        $this->info('✅ Verification completed');
    }

    private function verifyControllers()
    {
        $this->info('🎮 Verifying Performance Controllers:');
        
        $controllers = [
            'PerformanceController.php',
            'PerformanceDashboardController.php', 
            'PerformanceOptimizationController.php'
        ];
        
        foreach ($controllers as $controller) {
            $path = app_path("Http/Controllers/Api/{$controller}");
            if (File::exists($path)) {
                $content = File::get($path);
                
                // Check if used in routes
                $routeContent = File::get(base_path('routes/api.php'));
                $className = str_replace('.php', '', $controller);
                
                if (str_contains($routeContent, $className)) {
                    $this->error("  ❌ {$controller} - USED in routes - DO NOT DELETE");
                } else {
                    $this->warn("  ⚠️  {$controller} - Not found in routes - Check manually");
                }
            }
        }
    }

    private function verifyServices()
    {
        $this->info('⚙️ Verifying Cache Services:');
        
        $services = [
            'CacheManagementService.php',
            'CacheOptimizationService.php',
            'DatabaseOptimizationService.php'
        ];
        
        foreach ($services as $service) {
            $path = app_path("Services/{$service}");
            if (File::exists($path)) {
                $usageCount = $this->findServiceUsage($service);
                
                if ($usageCount > 0) {
                    $this->error("  ❌ {$service} - USED {$usageCount} times - DO NOT DELETE");
                } else {
                    $this->line("  ✅ {$service} - No usage found - Safe to review");
                }
            }
        }
    }

    private function verifyDirectories()
    {
        $this->info('📁 Verifying Directories:');
        
        $directories = [
            'storage/app/secrets' => 'May be used for secret storage',
            'storage/recordings' => 'May be used for audio recordings', 
            'storage/streams' => 'May be used for live streams',
            '-p' => 'Unknown directory'
        ];
        
        foreach ($directories as $dir => $purpose) {
            $fullPath = base_path($dir);
            
            if (File::exists($fullPath)) {
                // Check if referenced in code
                $referenced = $this->findDirectoryReferences($dir);
                
                if ($referenced) {
                    $this->error("  ❌ {$dir} - REFERENCED in code - DO NOT DELETE");
                } else {
                    $this->line("  ✅ {$dir} - No references found - Safe to delete");
                }
            }
        }
    }

    private function verifyConfigs()
    {
        $this->info('⚙️ Verifying Config Files:');
        
        $configs = [
            '.env.production' => 'Production environment file',
            'php-production.ini' => 'PHP production config',
            'deploy-production.sh' => 'Deployment script'
        ];
        
        foreach ($configs as $file => $purpose) {
            $path = base_path($file);
            
            if (File::exists($path)) {
                if (str_contains($file, 'production')) {
                    $this->warn("  ⚠️  {$file} - Production related - Keep for deployment");
                } else {
                    $this->line("  ✅ {$file} - Development only - Safe to delete");
                }
            }
        }
    }

    private function findServiceUsage(string $service): int
    {
        $className = str_replace('.php', '', $service);
        $count = 0;
        
        // Check controllers
        $controllers = File::allFiles(app_path('Http/Controllers'));
        foreach ($controllers as $controller) {
            $content = File::get($controller);
            if (str_contains($content, $className)) {
                $count++;
            }
        }
        
        // Check other services
        $services = File::allFiles(app_path('Services'));
        foreach ($services as $serviceFile) {
            if ($serviceFile->getFilename() === $service) continue;
            
            $content = File::get($serviceFile);
            if (str_contains($content, $className)) {
                $count++;
            }
        }
        
        return $count;
    }

    private function findDirectoryReferences(string $dir): bool
    {
        $searchPaths = [
            app_path(),
            config_path(),
            base_path('routes')
        ];
        
        foreach ($searchPaths as $searchPath) {
            $files = File::allFiles($searchPath);
            
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') continue;
                
                $content = File::get($file);
                if (str_contains($content, $dir)) {
                    return true;
                }
            }
        }
        
        return false;
    }
}