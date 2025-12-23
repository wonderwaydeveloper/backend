<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SearchSystemAnalysis extends Command
{
    protected $signature = 'search:analyze';
    protected $description = 'Comprehensive analysis of advanced search system';

    public function handle()
    {
        $this->info('🔍 Analyzing Advanced Search System...');
        
        $this->analyzeSearchEngines();
        $this->analyzeSearchFeatures();
        $this->analyzePerformance();
        $this->analyzeIndexing();
        $this->analyzeFilters();
        $this->evaluateQuality();
        
        $this->info('✅ Search system analysis completed');
    }

    private function analyzeSearchEngines()
    {
        $this->info('🔧 Search Engines Analysis:');
        
        // Check MeiliSearch
        if (File::exists(app_path('Services/SearchService.php'))) {
            $content = File::get(app_path('Services/SearchService.php'));
            if (str_contains($content, 'MeiliSearch')) {
                $this->line('  ✓ MeiliSearch: Primary search engine');
            }
        }
        
        // Check Elasticsearch
        if (File::exists(app_path('Services/ElasticsearchService.php'))) {
            $content = File::get(app_path('Services/ElasticsearchService.php'));
            if (str_contains($content, 'elasticsearch')) {
                $this->line('  ✓ Elasticsearch: Secondary search engine');
            }
        }
        
        // Check Laravel Scout
        if (File::exists(base_path('config/scout.php'))) {
            $this->line('  ✓ Laravel Scout: Search abstraction layer');
        }
    }

    private function analyzeSearchFeatures()
    {
        $this->info('🎯 Search Features Analysis:');
        
        $controller = app_path('Http/Controllers/Api/SearchController.php');
        if (File::exists($controller)) {
            $content = File::get($controller);
            
            $features = [
                'posts' => 'Post search',
                'users' => 'User search', 
                'hashtags' => 'Hashtag search',
                'all' => 'Universal search',
                'advanced' => 'Advanced search',
                'suggestions' => 'Search suggestions'
            ];
            
            foreach ($features as $method => $description) {
                if (str_contains($content, "function {$method}(")) {
                    $this->line("  ✓ {$description}");
                } else {
                    $this->warn("  ⚠️  {$description} - Missing");
                }
            }
        }
    }

    private function analyzePerformance()
    {
        $this->info('⚡ Performance Features:');
        
        $searchService = app_path('Services/SearchService.php');
        if (File::exists($searchService)) {
            $content = File::get($searchService);
            
            $performanceFeatures = [
                'attributesToHighlight' => 'Result highlighting',
                'limit' => 'Pagination support',
                'offset' => 'Offset pagination',
                'sort' => 'Sorting capabilities',
                'filter' => 'Advanced filtering'
            ];
            
            foreach ($performanceFeatures as $feature => $description) {
                if (str_contains($content, $feature)) {
                    $this->line("  ✓ {$description}");
                } else {
                    $this->warn("  ⚠️  {$description} - Missing");
                }
            }
        }
    }

    private function analyzeIndexing()
    {
        $this->info('📚 Indexing Capabilities:');
        
        $searchService = app_path('Services/SearchService.php');
        if (File::exists($searchService)) {
            $content = File::get($searchService);
            
            $indexingMethods = [
                'indexPost' => 'Post indexing',
                'indexUser' => 'User indexing', 
                'deletePost' => 'Document deletion'
            ];
            
            foreach ($indexingMethods as $method => $description) {
                if (str_contains($content, "function {$method}(")) {
                    $this->line("  ✓ {$description}");
                } else {
                    $this->warn("  ⚠️  {$description} - Missing");
                }
            }
        }
    }

    private function analyzeFilters()
    {
        $this->info('🔍 Advanced Filters:');
        
        $controller = app_path('Http/Controllers/Api/SearchController.php');
        if (File::exists($controller)) {
            $content = File::get($controller);
            
            $filters = [
                'user_id' => 'User-specific search',
                'has_media' => 'Media filtering',
                'date_from' => 'Date range filtering',
                'min_likes' => 'Popularity filtering',
                'hashtags' => 'Hashtag filtering',
                'verified' => 'Verified user filtering',
                'min_followers' => 'Follower count filtering',
                'location' => 'Location filtering'
            ];
            
            foreach ($filters as $filter => $description) {
                if (str_contains($content, $filter)) {
                    $this->line("  ✓ {$description}");
                } else {
                    $this->warn("  ⚠️  {$description} - Missing");
                }
            }
        }
    }

    private function evaluateQuality()
    {
        $this->info('📊 Search Quality Evaluation:');
        
        $scores = [];
        
        // Engine diversity
        $engines = 0;
        if (File::exists(app_path('Services/SearchService.php'))) $engines++;
        if (File::exists(app_path('Services/ElasticsearchService.php'))) $engines++;
        $scores['engines'] = min($engines * 2, 4);
        
        // Feature completeness
        $controller = app_path('Http/Controllers/Api/SearchController.php');
        $features = 0;
        if (File::exists($controller)) {
            $content = File::get($controller);
            $methods = ['posts', 'users', 'hashtags', 'all', 'advanced', 'suggestions'];
            foreach ($methods as $method) {
                if (str_contains($content, "function {$method}(")) $features++;
            }
        }
        $scores['features'] = min($features, 6);
        
        // Filter richness
        $filters = 0;
        if (File::exists($controller)) {
            $content = File::get($controller);
            $filterList = ['user_id', 'has_media', 'date_from', 'min_likes', 'hashtags', 'verified'];
            foreach ($filterList as $filter) {
                if (str_contains($content, $filter)) $filters++;
            }
        }
        $scores['filters'] = min($filters, 6);
        
        $totalScore = array_sum($scores);
        $maxScore = 16;
        $percentage = round(($totalScore / $maxScore) * 100);
        
        $this->line("  📊 Search Engines: {$scores['engines']}/4");
        $this->line("  📊 Features: {$scores['features']}/6");
        $this->line("  📊 Filters: {$scores['filters']}/6");
        $this->line("  📊 Total Score: {$totalScore}/{$maxScore} ({$percentage}%)");
        
        if ($percentage >= 90) {
            $this->info("  🏆 Excellent search system!");
        } elseif ($percentage >= 75) {
            $this->line("  ✅ Good search system");
        } elseif ($percentage >= 60) {
            $this->warn("  ⚠️  Average search system");
        } else {
            $this->error("  ❌ Poor search system");
        }
    }
}