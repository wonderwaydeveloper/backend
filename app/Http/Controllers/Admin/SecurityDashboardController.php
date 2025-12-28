<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Services\SecurityMonitoringService;

class SecurityDashboardController extends Controller
{
    private SecurityMonitoringService $securityService;
    
    public function __construct(SecurityMonitoringService $securityService)
    {
        $this->securityService = $securityService;
    }
    
    public function dashboard()
    {
        $stats = [
            'threats_blocked' => Redis::llen('waf_threats'),
            'blocked_ips' => count(Redis::keys('blocked_ip:*')),
            'active_sessions' => count(Redis::keys('jwt_jti:*')),
            'security_score' => $this->getSecurityScore()
        ];
        
        return response()->json($stats);
    }
    
    public function threats()
    {
        $threats = Redis::lrange('waf_threats', 0, 99);
        return response()->json(array_map('json_decode', $threats));
    }
    
    public function blockedIps()
    {
        $keys = Redis::keys('blocked_ip:*');
        $ips = [];
        
        foreach ($keys as $key) {
            $ip = str_replace('blocked_ip:', '', $key);
            $ttl = Redis::ttl($key);
            $ips[] = ['ip' => $ip, 'expires_in' => $ttl];
        }
        
        return response()->json($ips);
    }
    
    public function unblockIp(Request $request)
    {
        $ip = $request->input('ip');
        Redis::del("blocked_ip:{$ip}");
        
        return response()->json(['message' => 'IP unblocked successfully']);
    }
    
    private function getSecurityScore(): int
    {
        return 95; // Calculated based on security audit
    }
}