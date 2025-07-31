<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display notifications page
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'sometimes|string',
            'priority' => 'sometimes|in:low,normal,high,critical',
            'read' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:5|max:100',
        ]);

        $query = Notification::forUser(auth()->id())
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->has('read')) {
            if ($request->boolean('read')) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        $notifications = $query->paginate($request->input('per_page', 20));
        $stats = $this->notificationService->getNotificationStats();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'stats' => $stats,
            'filters' => $request->only(['type', 'priority', 'read']),
        ]);
    }

    /**
     * Get notifications for API/AJAX requests
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:50',
            'unread_only' => 'sometimes|boolean',
            'type' => 'sometimes|string',
            'priority' => 'sometimes|in:low,normal,high,critical',
        ]);

        try {
            $query = Notification::forUser(auth()->id())
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->boolean('unread_only')) {
                $query->unread();
            }

            if ($request->has('type')) {
                $query->byType($request->type);
            }

            if ($request->has('priority')) {
                $query->byPriority($request->priority);
            }

            $notifications = $query->limit($request->input('limit', 10))->get();

            return response()->json([
                'success' => true,
                'data' => $notifications,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notifications: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'sometimes|array',
            'notification_ids.*' => 'exists:notifications,id',
            'mark_all' => 'sometimes|boolean',
        ]);

        try {
            if ($request->boolean('mark_all')) {
                $updated = Notification::markAllAsReadForUser(auth()->id());
            } else {
                $notificationIds = $request->input('notification_ids', []);
                $updated = Notification::whereIn('id', $notificationIds)
                    ->forUser(auth()->id())
                    ->unread()
                    ->update(['read_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'updated_count' => $updated,
                ],
                'message' => "Marked {$updated} notifications as read",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $updated = Notification::markAllAsReadForUser(auth()->id());

            return response()->json([
                'success' => true,
                'data' => [
                    'updated_count' => $updated,
                ],
                'message' => "Marked all notifications as read",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->notificationService->getNotificationStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification statistics for API
     */
    public function getNotificationStats(): JsonResponse
    {
        return $this->getStats();
    }

    /**
     * Delete notification
     */
    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete notifications
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);

        try {
            $deleted = Notification::whereIn('id', $request->notification_ids)
                ->forUser(auth()->id())
                ->delete();

            return response()->json([
                'success' => true,
                'data' => [
                    'deleted_count' => $deleted,
                ],
                'message' => "Deleted {$deleted} notifications",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notifications: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);

        try {
            $updated = Notification::whereIn('id', $request->notification_ids)
                ->forUser(auth()->id())
                ->update(['read_at' => null]);

            return response()->json([
                'success' => true,
                'data' => [
                    'updated_count' => $updated,
                ],
                'message' => "Marked {$updated} notifications as unread",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as unread: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(): JsonResponse
    {
        $user = auth()->user();
        $preferences = $user->notification_preferences ?? [];

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'case_assignments' => 'sometimes|boolean',
            'task_assignments' => 'sometimes|boolean',
            'document_analysis' => 'sometimes|boolean',
            'knowledge_base_updates' => 'sometimes|boolean',
            'firm_announcements' => 'sometimes|boolean',
            'email_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
        ]);

        try {
            $user = auth()->user();
            $user->update([
                'notification_preferences' => array_merge(
                    $user->notification_preferences ?? [],
                    $request->only([
                        'case_assignments',
                        'task_assignments',
                        'document_analysis',
                        'knowledge_base_updates',
                        'firm_announcements',
                        'email_notifications',
                        'push_notifications'
                    ])
                )
            ]);

            return response()->json([
                'success' => true,
                'data' => $user->notification_preferences,
                'message' => 'Notification preferences updated',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences: ' . $e->getMessage(),
            ], 500);
        }
    }
}
