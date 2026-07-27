<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Get vendor notifications
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $notifications = $user->notifications()
                ->latest()
                ->paginate($request->limit ?? 20);

            $data = collect($notifications->items())->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? '',
                    'message' => $notification->data['message'] ?? '',
                    'type' => $notification->data['type'] ?? 'info',
                    'data' => $notification->data['data'] ?? [],
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });

            return $this->success([
                'notifications' => $data,
                'pagination' => [
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                ]
            ], 'Vendor notifications retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve notifications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        try {
            $notification = Auth::user()->notifications()->where('id', $id)->first();

            if (!$notification) {
                return $this->error([], 'Notification not found', 404);
            }

            $notification->markAsRead();

            return $this->success([], 'Notification marked as read');
        } catch (Exception $e) {
            return $this->error([], 'Failed to mark notification as read: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            Auth::user()->unreadNotifications->markAsRead();

            return $this->success([], 'All notifications marked as read');
        } catch (Exception $e) {
            return $this->error([], 'Failed to mark all notifications as read', 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        try {
            $notification = Auth::user()->notifications()->where('id', $id)->first();

            if (!$notification) {
                return $this->error([], 'Notification not found', 404);
            }

            $notification->delete();

            return $this->success([], 'Notification deleted');
        } catch (Exception $e) {
            return $this->error([], 'Failed to delete notification: ' . $e->getMessage(), 500);
        }
    }
}
