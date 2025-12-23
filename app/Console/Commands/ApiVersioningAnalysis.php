<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class ApiVersioningAnalysis extends Command
{
    protected $signature = 'api:versioning-analysis';
    protected $description = 'Analyze API versioning implementation and standards compliance';

    public function handle()
    {
        $this->info('🔍 Analyzing API Versioning Implementation...');
        
        $this->analyzeCurrentStructure();
        $this->analyzeVersioningStrategy();
        $this->analyzeStandardsCompliance();
        $this->analyzeBackwardCompatibility();
        $this->provideBestPractices();
        
        $this->info('✅ API versioning analysis completed');
    }

    private function analyzeCurrentStructure()
    {
        $this->info('📁 Current API Structure:');
        
        // Check route files
        $routeFiles = [
            'api.php' => 'Main API routes',
            'versioned-api.php' => 'Versioned API routes'
        ];
        
        foreach ($routeFiles as $file => $description) {
            if (File::exists(base_path("routes/{$file}"))) {
                $this->line("  ✓ {$file} - {$description}");
            }
        }
        
        // Check versioned controllers
        $v2Controllers = glob(app_path('Http/Controllers/Api/V2/*.php'));
        $this->line("  ✓ V2 Controllers: " . count($v2Controllers));
        
        // Check middleware
        if (File::exists(app_path('Http/Middleware'))) {
            $middlewares = File::files(app_path('Http/Middleware'));
            $versionMiddleware = collect($middlewares)->filter(function ($file) {
                return str_contains($file->getFilename(), 'version') || 
                       str_contains($file->getFilename(), 'Version');
            });
            
            $this->line("  ✓ Version Middleware: " . $versionMiddleware->count());
        }
    }

    private function analyzeVersioningStrategy()
    {
        $this->info('🎯 Versioning Strategy Analysis:');
        
        // Check URL versioning
        $apiContent = File::get(base_path('routes/api.php'));
        
        if (str_contains($apiContent, "prefix('v1')")) {
            $this->line('  ✓ URL Versioning: /api/v1/');
        }
        
        if (str_contains($apiContent, "prefix('v2')")) {
            $this->line('  ✓ URL Versioning: /api/v2/');
        }
        
        // Check header versioning
        if (str_contains($apiContent, 'api.version')) {
            $this->line('  ✓ Middleware-based versioning detected');
        }
        
        // Check version in response
        if (str_contains($apiContent, "'version' => '3.0.0'")) {
            $this->line('  ✓ Version info in health endpoint');
        }
    }

    private function analyzeStandardsCompliance()
    {
        $this->info('📋 Standards Compliance Check:');
        
        $issues = [];
        $good = [];
        
        // Check semantic versioning
        $apiContent = File::get(base_path('routes/api.php'));
        if (preg_match("/version.*(\d+\.\d+\.\d+)/", $apiContent)) {
            $good[] = 'Semantic versioning format used';
        } else {
            $issues[] = 'No semantic versioning detected';
        }
        
        // Check deprecation headers
        if (!str_contains($apiContent, 'Deprecation') && !str_contains($apiContent, 'Sunset')) {
            $issues[] = 'No deprecation headers for old versions';
        }
        
        // Check version negotiation
        if (!str_contains($apiContent, 'Accept') && !str_contains($apiContent, 'Content-Type')) {
            $issues[] = 'No content negotiation for versions';
        }
        
        // Check backward compatibility
        $v1Routes = substr_count($apiContent, "prefix('v1')");
        $v2Routes = substr_count($apiContent, "prefix('v2')");
        
        if ($v1Routes > 0 && $v2Routes > 0) {
            $good[] = 'Multiple versions maintained';
        }
        
        foreach ($good as $item) {
            $this->line("  ✓ {$item}");
        }
        
        foreach ($issues as $issue) {
            $this->warn("  ⚠️  {$issue}");
        }
    }

    private function analyzeBackwardCompatibility()
    {
        $this->info('🔄 Backward Compatibility Analysis:');
        
        // Check if old versions still work
        $apiContent = File::get(base_path('routes/api.php'));
        
        // Check for breaking changes indicators
        $breakingChanges = [
            'removed' => 'Route removal detected',
            'deprecated' => 'Deprecation notices found',
            'changed' => 'Parameter changes detected'
        ];
        
        $hasBreaking = false;
        foreach ($breakingChanges as $pattern => $message) {
            if (str_contains(strtolower($apiContent), $pattern)) {
                $this->warn("  ⚠️  {$message}");
                $hasBreaking = true;
            }
        }
        
        if (!$hasBreaking) {
            $this->line('  ✓ No obvious breaking changes detected');
        }
        
        // Check version migration path
        if (File::exists(base_path('routes/versioned-api.php'))) {
            $this->line('  ✓ Dedicated versioned routes file exists');
        }
    }

    private function provideBestPractices()
    {
        $this->info('💡 API Versioning Best Practices Recommendations:');
        
        $recommendations = [
            '1. Use semantic versioning (MAJOR.MINOR.PATCH)',
            '2. Add deprecation headers for old versions',
            '3. Implement content negotiation',
            '4. Maintain at least 2 versions simultaneously',
            '5. Document migration guides between versions',
            '6. Use sunset dates for version retirement',
            '7. Implement version-specific middleware',
            '8. Add version info to all responses'
        ];
        
        foreach ($recommendations as $rec) {
            $this->line("  💡 {$rec}");
        }
    }
}