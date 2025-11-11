<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with(['subject', 'causer'])->latest();

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        $logs = $query->paginate(50);

        $logNames = ActivityLog::distinct()->pluck('log_name');
        $events = ActivityLog::distinct()->pluck('event');

        return view('admin.activity-logs.index', compact('logs', 'logNames', 'events'));
    }

    public function show(ActivityLog $activityLog)
    {
        $activityLog->load(['subject', 'causer']);

        return view('admin.activity-logs.show', compact('activityLog'));
    }
}
