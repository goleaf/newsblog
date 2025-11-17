<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationGroupingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationGroupingService $groupingService
    ) {}

    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        // Group notifications if requested
        $grouped = $request->boolean('grouped', true)
            ? $this->groupingService->groupNotifications($notifications->getCollection())
            : $notifications->getCollection();

        if ($request->expectsJson()) {
            return response()->json([
                'notifications' => $grouped,
                'unread_count' => $user->unreadNotifications()->count(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
            ]);
        }

        // Replace the collection with grouped notifications
        $notifications->setCollection($grouped);

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get unread notifications for dropdown.
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->unreadNotifications()
            ->latest()
            ->limit(10)
            ->get();

        // Group notifications if requested
        $grouped = $request->boolean('grouped', true)
            ? $this->groupingService->groupNotifications($notifications)
            : $notifications;

        return response()->json([
            'success' => true,
            'notifications' => $grouped,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $notification = Notification::findOrFail($id);
        if ($notification->user_id !== $user->id) {
            abort(403);
        }
        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'notification' => $notification,
            ]);
        }

        return redirect()->back()->with('success', 'Notification marked as read');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
            ]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $notification = Notification::findOrFail($id);
        if ($notification->user_id !== $user->id) {
            abort(403);
        }
        $notification->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Notification deleted',
            ]);
        }

        return redirect()->back()->with('success', 'Notification deleted');
    }

    /**
     * Delete all read notifications.
     */
    public function deleteRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $user->readNotifications()->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'All read notifications deleted',
            ]);
        }

        return redirect()->back()->with('success', 'All read notifications deleted');
    }

    /**
     * Get notification preferences.
     */
    public function preferences(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $preferences = $user->getEmailPreferences();

        if ($request->expectsJson()) {
            return response()->json([
                'preferences' => $preferences,
            ]);
        }

        return view('notifications.preferences', [
            'preferences' => $preferences,
        ]);
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'comment_replies' => 'boolean',
            'post_published' => 'boolean',
            'comment_approved' => 'boolean',
            'series_updated' => 'boolean',
            'newsletter' => 'boolean',
            'new_followers' => 'boolean',
            'author_new_article' => 'boolean',
            'comment_reactions' => 'boolean',
            'mentions' => 'boolean',
            'frequency' => 'in:immediate,daily,weekly',
        ]);

        $user = $request->user();
        $user->updateEmailPreferences($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Notification preferences updated',
                'preferences' => $user->getEmailPreferences(),
            ]);
        }

        return redirect()->back()->with('success', 'Notification preferences updated successfully');
    }

    /**
     * Get notification count.
     */
    public function count(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count' => $user->notifications()->count(),
        ]);
    }
}
