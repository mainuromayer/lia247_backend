<?php

namespace App\Services;

use App\Helper\Helper;
use App\Models\FirebaseToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserDeletionService
{
    /**
     * Soft-delete a user and anonymize/archive credentials according to requirements.
     * Replaces email with delete_user_{id}@gmail.com, resets password to 12345678,
     * sets status to inactive, and soft-deletes the record to prevent any operations.
     */
    public static function deleteUser(User $user): bool
    {
        // Save original email before replacement if not already saved
        if (empty($user->original_email)) {
            $user->original_email = $user->email;
        }

        // Replace email with pattern delete_user_{id}@gmail.com
        $user->email = 'delete_user_' . $user->id . '@gmail.com';

        // Set default password to 12345678 (hashed automatically via casts)
        $user->password = '12345678';

        // Invalidate status
        $user->status = 'inactive';
        $user->save();

        // Revoke push tokens & active sessions
        try {
            FirebaseToken::where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        } catch (\Throwable $e) {
            Log::warning("Error revoking tokens/sessions for User #{$user->id}: " . $e->getMessage());
        }

        // Execute soft delete
        $deleted = $user->delete();

        Log::info("User #{$user->id} soft-deleted. Email replaced with {$user->email}. Original email saved: {$user->original_email}");

        return (bool) $deleted;
    }

    /**
     * Restore a soft-deleted trash user back to active status.
     * Restores original email if available and not conflicted.
     */
    public static function restoreUser(User $user): array
    {
        if (!$user->trashed()) {
            return [
                'success' => false,
                'message' => 'User is not in trash.',
            ];
        }

        // Restore original email if exists
        if (!empty($user->original_email)) {
            $emailConflict = User::where('email', $user->original_email)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($emailConflict) {
                return [
                    'success' => false,
                    'message' => "Cannot restore original email ({$user->original_email}) because another active user has registered with it. Please resolve conflict manually.",
                ];
            }

            $user->email = $user->original_email;
            $user->original_email = null;
        }

        $user->status = 'active';
        $user->restore();
        $user->save();

        Log::info("User #{$user->id} restored from trash. Current email: {$user->email}");

        return [
            'success' => true,
            'message' => 'User account restored successfully.',
        ];
    }

    /**
     * Permanently delete a user from the database.
     */
    public static function forceDeleteUser(User $user): bool
    {
        try {
            if ($user->avatar) {
                Helper::deleteFile($user->avatar);
            }

            FirebaseToken::where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        } catch (\Throwable $e) {
            Log::warning("Cleanup error before force-delete User #{$user->id}: " . $e->getMessage());
        }

        $forceDeleted = $user->forceDelete();

        Log::info("User #{$user->id} permanently deleted from database.");

        return (bool) $forceDeleted;
    }
}
