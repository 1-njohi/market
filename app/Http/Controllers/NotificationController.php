<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Returns the paginated notification list with unread count.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?? [];

                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? 'general',
                    'title' => $data['title'] ?? null,
                    'message' => $data['body'] ?? '',
                    'betslip_id' => $data['betslip_id'] ?? null,
                    'betslip_code' => $data['betslip_code'] ?? null,
                    'time' => $notification->created_at->diffForHumans(),
                    'created_at' => $notification->created_at->toISOString(),
                    'read' => !is_null($notification->read_at),
                    'read_at' => $notification->read_at?->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * POST /api/notifications/{id}/read
     * Mark a single notification as read.
     */
    public function markAsRead(string $id)
    {
        $user = Auth::user();

        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * POST /api/notifications/read-all
     * Mark every notification as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }

    /**
     * DELETE /api/notifications/{id}
     * Optional: dismiss a single notification.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $deleted = $user->notifications()->where('id', $id)->delete();

        return response()->json([
            'success' => (bool) $deleted,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }
}