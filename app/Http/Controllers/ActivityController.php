<?php

namespace App\Http\Controllers;

use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        protected ActivityService $activityService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display the user's activity feed
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $type = $request->query('type');
        $limit = (int) $request->query('limit', 20);

        $activities = $this->activityService->getUserActivityFeed($user, $limit);

        // Filter by type if specified
        if ($type) {
            $activities = $this->activityService->filterByType($activities, $type);
        }

        // Aggregate similar activities
        $activities = $this->activityService->aggregateSimilarActivities($activities);

        return view('activities.index', [
            'activities' => $activities,
            'currentType' => $type,
        ]);
    }

    /**
     * Display activity feed from users the current user follows
     */
    public function following(Request $request): View
    {
        $user = $request->user();

        $type = $request->query('type');
        $limit = (int) $request->query('limit', 20);

        $activities = $this->activityService->getFollowingActivityFeed($user, $limit);

        // Filter by type if specified
        if ($type) {
            $activities = $this->activityService->filterByType($activities, $type);
        }

        // Aggregate similar activities
        $activities = $this->activityService->aggregateSimilarActivities($activities);

        return view('activities.following', [
            'activities' => $activities,
            'currentType' => $type,
        ]);
    }

    /**
     * Display a specific activity detail
     */
    public function show(Request $request, int $id): View
    {
        $activity = \App\Models\Activity::with(['subject'])
            ->findOrFail($id);

        // Format the activity
        $formattedActivity = [
            'id' => $activity->id,
            'actor_id' => $activity->actor_id,
            'actor_name' => $activity->actor?->name,
            'actor_avatar' => $activity->actor?->avatar,
            'verb' => $activity->verb,
            'subject_type' => $activity->subject_type,
            'subject' => $activity->subject,
            'meta' => $activity->meta,
            'created_at' => $activity->created_at,
        ];

        return view('activities.show', [
            'activity' => $formattedActivity,
        ]);
    }
}
