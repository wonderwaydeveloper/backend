<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditTrailService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    public function __construct(
        private AuditTrailService $auditService
    ) {}

    /**
     * Get user's audit trail
     */
    public function getUserAuditTrail(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'nullable|string',
            'days' => 'nullable|integer|min:1|max:90'
        ]);

        $userId = Auth::id();
        $action = $request->input('action');
        $days = $request->input('days', 30);

        $auditTrail = $this->auditService->getAuditTrail($userId, $action, $days);
        $summary = $this->auditService->getUserActivitySummary($userId, $days);

        return response()->json([
            'audit_trail' => $auditTrail,
            'summary' => $summary
        ]);
    }

    /**
     * Get security events (admin only)
     */
    public function getSecurityEvents(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AuditLog::class);

        $request->validate([
            'days' => 'nullable|integer|min:1|max:30'
        ]);

        $days = $request->input('days', 7);
        $events = $this->auditService->getSecurityEvents($days);

        return response()->json([
            'security_events' => $events,
            'total_count' => $events->count(),
            'period_days' => $days
        ]);
    }

    /**
     * Get high-risk activities (admin only)
     */
    public function getHighRiskActivities(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AuditLog::class);

        $request->validate([
            'days' => 'nullable|integer|min:1|max:7'
        ]);

        $days = $request->input('days', 1);
        $activities = $this->auditService->getHighRiskActivities($days);

        return response()->json([
            'high_risk_activities' => $activities,
            'total_count' => $activities->count(),
            'period_days' => $days
        ]);
    }

    /**
     * Detect anomalous activity for current user
     */
    public function detectAnomalies(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $anomalies = $this->auditService->detectAnomalousActivity($userId);

        return response()->json([
            'anomalies' => $anomalies,
            'anomaly_count' => count($anomalies),
            'user_id' => $userId
        ]);
    }

    /**
     * Get audit statistics (admin only)
     */
    public function getAuditStatistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\AuditLog::class);

        $request->validate([
            'days' => 'nullable|integer|min:1|max:30'
        ]);

        $days = $request->input('days', 7);
        
        $stats = \App\Models\AuditLog::where('timestamp', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_events,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT ip_address) as unique_ips,
                SUM(CASE WHEN risk_level = "high" THEN 1 ELSE 0 END) as high_risk_count,
                SUM(CASE WHEN action LIKE "security.%" THEN 1 ELSE 0 END) as security_events_count
            ')
            ->first();

        $topActions = \App\Models\AuditLog::where('timestamp', '>=', now()->subDays($days))
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit(config('pagination.trending'))
            ->get();

        return response()->json([
            'statistics' => $stats,
            'top_actions' => $topActions,
            'period_days' => $days
        ]);
    }
}