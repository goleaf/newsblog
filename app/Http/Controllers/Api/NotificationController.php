<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\NotificationPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status'); // all|unread|read

        $query = $user->notifications()->latest('created_at');
        if ($status === 'unread') {
            $query->unread();
        } elseif ($status === 'read') {
            $query->read();
        }

        $page = $query->paginate(20);

        return response()->json([
            'data' => NotificationResource::collection($page->items()),
            'total' => $page->total(),
            'links' => [
                'next' => $page->nextPageUrl(),
                'prev' => $page->previousPageUrl(),
            ],
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = $user->notifications()->unread()->latest('created_at')->limit(20)->get();

        return response()->json([
            'count' => $user->notifications()->unread()->count(),
            'data' => NotificationResource::collection($items),
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->markAsRead();

        return response()->json(['status' => 'ok']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->notifications()->unread()->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->delete();

        return response()->json(null, 204);
    }

    public function preferences(Request $request): JsonResponse
    {
        $prefs = NotificationPreferences::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['email_enabled' => true, 'push_enabled' => false, 'digest_frequency' => 'weekly']
        );

        return response()->json([
            'email_enabled' => (bool) $prefs->email_enabled,
            'push_enabled' => (bool) $prefs->push_enabled,
            'digest_frequency' => (string) $prefs->digest_frequency,
            'channels' => $prefs->channels ?? null,
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_enabled' => ['boolean'],
            'push_enabled' => ['boolean'],
            'digest_frequency' => ['in:none,daily,weekly'],
            'channels' => ['nullable', 'array'],
        ]);

        $prefs = NotificationPreferences::firstOrCreate(['user_id' => $request->user()->id]);
        $prefs->update($validated);

        return response()->json(['status' => 'ok']);
    }
}
