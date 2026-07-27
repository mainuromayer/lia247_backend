<?php

namespace App\Services;

use App\Helper\Helper;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public static function send($userId, $title, $body, $type = 'info', $role = null, $data = [])
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return null;
            }

            // Check settings before notifying
            if (!$user->push_notification) {
                // If push is disabled, we still save to database manually if needed, 
                // but UniversalNotification's 'via' method can handle this if we customize it.
                // For now, let's just use the standard Laravel notification flow.
            }

            // Check specific order/ride request setting
            if (in_array($type, ['order', 'ride']) && isset($data['is_request']) && $data['is_request'] === true) {
                if (!$user->order_request_notification) {
                    return null;
                }
            }

            $user->notify(new \App\Notifications\UniversalNotification($title, $body, $type, $data));

            return true;
        } catch (Exception $e) {
            Log::error("Notification sending error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send notification to multiple users (e.g. all admins)
     */
    public static function sendToRole($role, $title, $body, $type = null, $data = [])
    {
        $users = User::role($role)->get();
        foreach ($users as $user) {
            self::send($user->id, $title, $body, $type, $role, $data);
        }
    }
}
