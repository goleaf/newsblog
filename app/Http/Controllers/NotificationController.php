<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteNotificationRequest;
use App\Http\Requests\MarkAllNotificationsReadRequest;
use App\Http\Requests\MarkNotificationReadRequest;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display all notifications.
     */
    public function index(): View
    {
        $notifications = $this->notificationService->getAll(auth()->user());

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Get unread notifications (for dropdown).
     */
    public function unread(): JsonResponse
    {
        $notifications = $this->notificationService->getUnread(auth()->user());
        $unreadCount = $this->notificationService->getUnreadCount(auth()->user());

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(MarkNotificationReadRequest $request, Notification $notification): JsonResponse
    {
        // Ensure the notification belongs to the authenticated user
        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized'),
            ], 403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'success' => true,
            'message' => __('Notification marked as read'),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(MarkAllNotificationsReadRequest $request): JsonResponse
    {
        $this->notificationService->markAllAsRead(auth()->user());

        return response()->json([
            'success' => true,
            'message' => __('All notifications marked as read'),
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(DeleteNotificationRequest $request, Notification $notification): JsonResponse
    {
        // Ensure the notification belongs to the authenticated user
        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized'),
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => __('Notification deleted'),
        ]);
    }
}
