<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModerationController extends Controller
{
    public function reportPost(Request $request, Post $post)
    {
        $request->validate([
            'reason' => 'required|string|in:spam,harassment,hate_speech,violence,nudity,other',
            'description' => 'nullable|string|max:500',
        ]);
        
        return $this->createReport($request, $post, 'App\\Models\\Post');
    }

    public function reportUser(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot report yourself'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $request->validate([
            'reason' => 'required|string|in:spam,harassment,hate_speech,violence,nudity,other',
            'description' => 'nullable|string|max:500',
        ]);
        
        return $this->createReport($request, $user, 'App\\Models\\User');
    }

    public function reportComment(Request $request, Comment $comment)
    {
        $request->validate([
            'reason' => 'required|string|in:spam,harassment,hate_speech,violence,nudity,other',
            'description' => 'nullable|string|max:500',
        ]);
        
        return $this->createReport($request, $comment, 'App\\Models\\Comment');
    }

    private function createReport(Request $request, $reportable, $type)
    {
        // Check if user already reported this content
        $existingReport = Report::where('reporter_id', auth()->id())
            ->where('reportable_type', $type)
            ->where('reportable_id', $reportable->id)
            ->first();

        if ($existingReport) {
            return response()->json(['message' => 'You have already reported this content'], Response::HTTP_BAD_REQUEST);
        }

        // Create report
        $report = new Report();
        $report->reporter_id = auth()->id();
        $report->reportable_type = $type;
        $report->reportable_id = $reportable->id;
        $report->reason = $request->input('reason');
        $report->description = $request->input('description');
        $report->status = 'pending';
        $report->save();

        $this->autoModerate($type, $reportable->id);

        return response()->json([
            'message' => 'Thank you for reporting. We will review this content.',
            'report_id' => $report->id
        ]);
    }

    public function myReports(Request $request)
    {
        $reports = Report::where('reporter_id', auth()->id())
            ->with('reportable')
            ->orderBy('created_at', 'desc')
            ->paginate(config('limits.pagination.reports'));

        return response()->json($reports);
    }

    public function getReports(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,reviewed,resolved,rejected',
            'type' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Report::with(['reporter:id,name,username', 'reviewer:id,name,username', 'reportable'])
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('reportable_type', $request->type);
        }

        return response()->json($query->paginate($request->per_page ?? config('limits.pagination.reports')));
    }

    public function showReport(Report $report)
    {
        return response()->json($report->load(['reporter', 'reviewer', 'reportable']));
    }

    public function updateReportStatus(Request $request, Report $report)
    {
        $request->validate([
            'status' => 'required|in:reviewed,resolved,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $report->status = $request->status;
        $report->admin_notes = $request->admin_notes;
        $report->reviewed_by = auth()->id();
        $report->reviewed_at = now();
        $report->save();

        return response()->json(['message' => 'Report status updated']);
    }

    public function takeAction(Request $request, Report $report)
    {
        $request->validate([
            'action' => 'required|in:dismiss,warn,remove_content,suspend_user,ban_user'
        ]);

        $this->executeAction($report, $request->action);

        $report->status = 'resolved';
        $report->action_taken = $request->action;
        $report->reviewed_by = auth()->id();
        $report->reviewed_at = now();
        $report->save();

        return response()->json(['message' => 'Action taken successfully']);
    }

    public function getContentStats()
    {
        return response()->json([
            'reports' => [
                'total' => Report::count(),
                'pending' => Report::where('status', 'pending')->count(),
                'reviewed' => Report::where('status', 'reviewed')->count(),
                'resolved' => Report::where('status', 'resolved')->count(),
            ],
            'content' => [
                'total_posts' => Post::count(),
                'flagged_posts' => Post::where('is_flagged', true)->count(),
            ],
        ]);
    }

    private function autoModerate($type, $id)
    {
        $reportCount = Report::where('reportable_type', $type)
            ->where('reportable_id', $id)
            ->where('status', 'pending')
            ->count();

        // Check for high spam score reports
        $highSpamReports = Report::where('reportable_type', $type)
            ->where('reportable_id', $id)
            ->where('auto_detected', true)
            ->where('spam_score', '>=', 90)
            ->count();

        if ($reportCount >= 5 || $highSpamReports >= 1) {
            if ($type === 'App\\Models\\Post') {
                Post::where('id', $id)->update(['is_flagged' => true]);
            }
        }

        if ($reportCount >= 10 || $highSpamReports >= 2) {
            if ($type === 'App\\Models\\Post') {
                Post::where('id', $id)->update(['is_hidden' => true]);
            }
        }
    }

    private function executeAction($report, $action)
    {
        switch ($action) {
            case 'dismiss':
                // No action needed - just mark as resolved
                break;
            case 'warn':
                // Send warning notification to user
                if ($report->reportable_type === 'App\\Models\\User') {
                    $user = User::find($report->reportable_id);
                    if ($user) {
                        $user->notify(new \App\Notifications\ModerationWarning($report));
                    }
                } elseif ($report->reportable_type === 'App\\Models\\Post') {
                    $post = Post::find($report->reportable_id);
                    if ($post && $post->user) {
                        $post->user->notify(new \App\Notifications\ModerationWarning($report));
                    }
                } elseif ($report->reportable_type === 'App\\Models\\Comment') {
                    $comment = Comment::find($report->reportable_id);
                    if ($comment && $comment->user) {
                        $comment->user->notify(new \App\Notifications\ModerationWarning($report));
                    }
                }
                break;
            case 'remove_content':
                if ($report->reportable_type === 'App\\Models\\Post') {
                    Post::where('id', $report->reportable_id)->delete();
                } elseif ($report->reportable_type === 'App\\Models\\Comment') {
                    Comment::where('id', $report->reportable_id)->delete();
                }
                break;
            case 'suspend_user':
                if ($report->reportable_type === 'App\\Models\\User') {
                    User::where('id', $report->reportable_id)->update([
                        'is_suspended' => true,
                        'suspended_until' => now()->addDays(7),
                    ]);
                }
                break;
            case 'ban_user':
                if ($report->reportable_type === 'App\\Models\\User') {
                    User::where('id', $report->reportable_id)->update([
                        'is_banned' => true,
                        'banned_at' => now(),
                    ]);
                }
                break;
        }
    }
}
