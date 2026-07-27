<?php

namespace App\Http\Controllers\API\V1\Rider;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Get rider notifications
     */
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user() ?? Auth::user();
            if (!$user) {
                return $this->error([], 'Unauthorized', 401);
            }
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
            ], 'Rider notifications retrieved successfully');
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
            $user = auth('api')->user() ?? Auth::user();
            if (!$user) {
                return $this->error([], 'Unauthorized', 401);
            }
            $notification = $user->notifications()->where('id', $id)->first();
            
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
            $user = auth('api')->user() ?? Auth::user();
            if (!$user) {
                return $this->error([], 'Unauthorized', 401);
            }
            $user->unreadNotifications->markAsRead();

            return $this->success([], 'All notifications marked as read');
        } catch (Exception $e) {
            return $this->error([], 'Failed to mark all notifications as read' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        try {
            $user = auth('api')->user() ?? Auth::user();
            if (!$user) {
                return $this->error([], 'Unauthorized', 401);
            }
            $notification = $user->notifications()->where('id', $id)->first();
            
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
