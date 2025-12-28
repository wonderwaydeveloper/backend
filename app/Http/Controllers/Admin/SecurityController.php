<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdvancedRateLimiter;
use App\Services\SecureJWTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class SecurityController extends Controller
{
    private AdvancedRateLimiter $rateLimiter;
    private SecureJWTService $jwtService;
    
    public function __construct(AdvancedRateLimiter $rateLimiter, SecureJWTService $jwtService)
    {
        $this->rateLimiter = $rateLimiter;
        $this->jwtService = $jwtService;
    }
    
    public function dashboard()
    {
        $stats = [
            'rate_limiter' => $this->rateLimiter->getStatistics(),
            'jwt' => $this->jwtService->getTokenStatistics(),
            'waf' => $this->getWafStatistics(),
            'threats' => $this->getRecentThreats(),
            'blocked_ips' => $this->rateLimiter->getBlockedIps()
        ];
        
        return response()->json($stats);
    }
    
    public function getWafStatistics()
    {
        $today = date('Y-m-d');
        
        return [
            'threats_blocked_today' => Redis::get("waf_threats_count:{$today}") ?: 0,
            'sql_injection_attempts' => Redis::get("waf_sql_attempts:{$today}") ?: 0,
            'xss_attempts' => Redis::get("waf_xss_attempts:{$today}") ?: 0,
            'file_inclusion_attempts' => Redis::get("waf_lfi_attempts:{$today}") ?: 0,
            'suspicious_agents' => Redis::get("waf_suspicious_agents:{$today}") ?: 0
        ];
    }
    
    public function getRecentThreats(int $limit = 50)
    {
        $threats = Redis::lrange('waf_threats', 0, $limit - 1);
        
        return array_map(function($threat) {
            return json_decode($threat, true);
        }, $threats);
    }
    
    public function blockIp(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip',
            'duration' => 'required|integer|min:60|max:86400',
            'reason' => 'required|string|max:255'
        ]);
        
        $this->rateLimiter->blockIpTemporarily(
            $request->ip,
            $request->duration
        );
        
        Log::warning('IP manually blocked', [
            'ip' => $request->ip,
            'duration' => $request->duration,
            'reason' => $request->reason,
            'admin_user' => auth()->id()
        ]);
        
        return response()->json([
            'message' => 'IP blocked successfully',
            'ip' => $request->ip,
            'duration' => $request->duration
        ]);
    }
    
    public function unblockIp(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip'
        ]);
        
        Redis::del("blocked_ip:{$request->ip}");
        
        Log::info('IP manually unblocked', [
            'ip' => $request->ip,
            'admin_user' => auth()->id()
        ]);
        
        return response()->json([
            'message' => 'IP unblocked successfully',
            'ip' => $request->ip
        ]);
    }
    
    public function getActiveSessions(Request $request)
    {
        $userId = $request->input('user_id');
        
        if ($userId) {
            $sessions = $this->jwtService->getActiveSessions($userId);
        } else {
            $sessionKeys = Redis::keys('session:*');
            $sessions = [];
            
            foreach ($sessionKeys as $key) {
                $sessionData = Redis::get($key);
                if ($sessionData) {
                    $session = json_decode($sessionData, true);
                    $sessions[] = $session;
                }
            }
        }
        
        return response()->json($sessions);
    }
    
    public function invalidateSession(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'session_id' => 'required|string'
        ]);
        
        $this->jwtService->invalidateSession(
            $request->user_id,
            $request->session_id
        );
        
        return response()->json([
            'message' => 'Session invalidated successfully'
        ]);
    }
    
    public function getSecurityLogs(Request $request)
    {
        $limit = $request->input('limit', 100);
        $threats = $this->getRecentThreats($limit);
        
        return response()->json($threats);
    }
    
    public function updateWafRules(Request $request)
    {
        $request->validate([
            'rules' => 'required|array',
            'rules.*.pattern' => 'required|string',
            'rules.*.score' => 'required|integer|min:1|max:100',
            'rules.*.type' => 'required|in:sql,xss,lfi,rfi,custom'
        ]);
        
        Redis::set('waf_custom_rules', json_encode($request->rules));
        
        return response()->json([
            'message' => 'WAF rules updated successfully',
            'rules_count' => count($request->rules)
        ]);
    }
}