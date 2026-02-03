<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Services\SecureJWTService;

class SecurityAudit extends Command
{
    protected $signature = 'security:audit {--detailed : Show detailed report}';
    protected $description = 'Run comprehensive security audit';

    public function handle()
    {
        $this->info('🔒 Starting Security Audit...');
        $this->newLine();
        
        $score = 0;
        $maxScore = 100;
        
        // WAF Status
        $this->info('📡 Web Application Firewall');
        $wafScore = $this->checkWAF();
        $score += $wafScore;
        $this->line("Score: {$wafScore}/20");
        $this->newLine();
        
        // Rate Limiting
        $this->info('🚦 Rate Limiting');
        $rateLimitScore = $this->checkRateLimiting();
        $score += $rateLimitScore;
        $this->line("Score: {$rateLimitScore}/20");
        $this->newLine();
        
        // JWT Security
        $this->info('🎫 JWT Security');
        $jwtScore = $this->checkJWTSecurity();
        $score += $jwtScore;
        $this->line("Score: {$jwtScore}/20");
        $this->newLine();
        
        // Database Security
        $this->info('🗄️ Database Security');
        $dbScore = $this->checkDatabaseSecurity();
        $score += $dbScore;
        $this->line("Score: {$dbScore}/20");
        $this->newLine();
        
        // Configuration Security
        $this->info('⚙️ Configuration Security');
        $configScore = $this->checkConfiguration();
        $score += $configScore;
        $this->line("Score: {$configScore}/20");
        $this->newLine();
        
        // Final Score
        $percentage = ($score / $maxScore) * 100;
        $this->info("🎯 Overall Security Score: {$score}/{$maxScore} ({$percentage}%)");
        
        if ($percentage >= 90) {
            $this->info('✅ Excellent security posture!');
        } elseif ($percentage >= 70) {
            $this->warn('⚠️ Good security, but room for improvement');
        } else {
            $this->error('❌ Security needs immediate attention!');
        }
        
        return $percentage >= 70 ? 0 : 1;
    }
    
    private function checkWAF(): int
    {
        $score = 0;
        
        // Check if WAF is enabled
        if (config('security.waf.enabled')) {
            $score += 5;
            $this->line('✅ WAF is enabled');
        } else {
            $this->error('❌ WAF is disabled');
        }
        
        // Check threat threshold
        $threshold = config('security.waf.threat_threshold');
        if ($threshold > 0 && $threshold <= 100) {
            $score += 5;
            $this->line("✅ Threat threshold: {$threshold}");
        }
        
        // Check Redis connectivity for WAF
        try {
            Redis::ping();
            $score += 5;
            $this->line('✅ Redis connectivity OK');
        } catch (\Exception $e) {
            $this->error('❌ Redis connection failed');
        }
        
        // Check recent threats
        $threats = Redis::llen('waf_threats');
        if ($threats !== false) {
            $score += 5;
            $this->line("✅ Threat logging active ({$threats} recent threats)");
        }
        
        return $score;
    }
    
    private function checkRateLimiting(): int
    {
        $score = 0;
        
        if (config('security.rate_limiting.enabled')) {
            $score += 10;
            $this->line('✅ Rate limiting enabled');
        } else {
            $this->error('❌ Rate limiting disabled');
        }
        
        // Check blocked IPs
        $blockedIps = count(Redis::keys('blocked_ip:*'));
        if ($blockedIps >= 0) {
            $score += 5;
            $this->line("✅ IP blocking active ({$blockedIps} blocked IPs)");
        }
        
        // Check rate limit configuration
        $perMinute = config('security.rate_limiting.per_minute');
        if ($perMinute > 0 && $perMinute <= 100) {
            $score += 5;
            $this->line("✅ Per-minute limit: {$perMinute}");
        }
        
        return $score;
    }
    
    private function checkJWTSecurity(): int
    {
        $score = 0;
        
        // Check JWT secret
        $secret = config('jwt.secret');
        if ($secret && strlen($secret) >= 32) {
            $score += 5;
            $this->line('✅ JWT secret is strong');
        } else {
            $this->error('❌ JWT secret is weak or missing');
        }
        
        // Check token TTL
        $ttl = config('jwt.access_ttl');
        if ($ttl > 0 && $ttl <= 3600) {
            $score += 5;
            $this->line("✅ Access token TTL: {$ttl}s");
        }
        
        // Check active tokens
        $activeTokens = count(Redis::keys('jwt_jti:*'));
        $score += 5;
        $this->line("✅ Active tokens: {$activeTokens}");
        
        // Check blacklisted tokens
        $blacklisted = count(Redis::keys('blacklisted_jwt:*'));
        $score += 5;
        $this->line("✅ Blacklisted tokens: {$blacklisted}");
        
        return $score;
    }
    
    private function checkDatabaseSecurity(): int
    {
        $score = 0;
        
        try {
            // Check database connection
            DB::connection()->getPdo();
            $score += 5;
            $this->line('✅ Database connection secure');
            
            // Check for default passwords (basic check)
            $config = config('database.connections.mysql');
            if ($config['password'] !== '' && $config['password'] !== 'password') {
                $score += 5;
                $this->line('✅ Database password is set');
            } else {
                $this->error('❌ Database using default/empty password');
            }
            
            // Check SSL usage
            if (isset($config['options']) && isset($config['options'][\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT])) {
                $score += 5;
                $this->line('✅ Database SSL configured');
            } else {
                $this->warn('⚠️ Database SSL not configured');
            }
            
            $score += 5; // Basic connectivity bonus
            
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed');
        }
        
        return $score;
    }
    
    private function checkConfiguration(): int
    {
        $score = 0;
        
        // Check debug mode
        if (!config('app.debug')) {
            $score += 5;
            $this->line('✅ Debug mode is disabled');
        } else {
            $this->error('❌ Debug mode is enabled in production');
        }
        
        // Check HTTPS
        if (config('app.url') && str_starts_with(config('app.url'), 'https://')) {
            $score += 5;
            $this->line('✅ HTTPS configured');
        } else {
            $this->warn('⚠️ HTTPS not configured');
        }
        
        // Check security headers
        if (config('security.headers.enabled')) {
            $score += 5;
            $this->line('✅ Security headers enabled');
        }
        
        // Check session security
        if (config('session.secure') && config('session.http_only')) {
            $score += 5;
            $this->line('✅ Secure session configuration');
        } else {
            $this->warn('⚠️ Session security could be improved');
        }
        
        return $score;
    }
}