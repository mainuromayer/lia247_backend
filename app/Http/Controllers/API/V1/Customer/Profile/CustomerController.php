<?php

namespace App\Http\Controllers\API\V1\Customer\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Customer\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use ApiResponse;

    public function me()
    {
        $user = Auth::user();
        return $this->success([
            'user' => new UserResource($user)
        ], 'Customer profile retrieved successfully', 200);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validation = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:250',
            'phone' => 'sometimes|string|max:250',
            'password' => 'sometimes|string|max:250|confirmed',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $updateData = $request->only([
                'name',
                'phone',
                'password',
            ]);

            if (isset($updateData['password'])) {
                $updateData['password'] = Hash::make($updateData['password']);
            }

            $user->update($updateData);
            DB::commit();

            return $this->success([
                'user' => new UserResource($user->fresh()),
            ], 'Customer profile updated successfully');

        }
        catch (Exception $e) {
            DB::rollBack();
            Log::error('Customer Profile update failed: ' . $e->getMessage());
            return $this->error([], 'Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    public function changePassword(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

        try {
            $user = Auth::user();
            if (Hash::check($request->current_password, $user->password)) {
                $user->password = Hash::make($request->password);
                $user->save();
                return $this->success([], "Password changed successfully", 200);
            }
            else {
                return $this->error([], "Current password is incorrect", 400);
            }
        }
        catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function deleteAccount()
    {
        $user = Auth::user();
        try {
            \App\Services\UserDeletionService::deleteUser($user);
            return $this->success([], "Customer account deleted successfully", 200);
        }
        catch (Exception $e) {
            Log::error('Customer Account delete failed: ' . $e->getMessage());
            return $this->error([], 'Failed to delete account', 500);
        }
    }

    public function updateNotificationSettings(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'push_notification' => 'sometimes|boolean',
            'email_notification' => 'sometimes|boolean',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 422);
        }

        try {
            $user = Auth::user();
            $user->update($request->only(['push_notification', 'email_notification']));
            
            return $this->success([
                'push_notification' => (bool) $user->push_notification,
                'email_notification' => (bool) $user->email_notification
            ], 'Notification settings updated successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to update notification settings', 500);
        }
    }

}
