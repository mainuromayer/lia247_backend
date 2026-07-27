<?php

namespace App\Http\Controllers\API;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLocation;
use App\Traits\ApiResponse;
use Exception;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\API\V1\User\AvatarUpdateRequest;
use App\Http\Requests\API\V1\User\CoverPhotoUpdateRequest;
use App\Http\Resources\API\V1\User\UserResource;

class UserController extends Controller
{
    use ApiResponse;


    public function updateUserInfo(Request $request)
    {
        $user = Auth::user();

        $validation = Validator::make($request->all(), [
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'name' => ['string', 'max:250'], // Removed 'nullable' to make it required
            'date_of_birth' => ['nullable', 'date'],
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 500);
        }

        DB::beginTransaction();
        try {
            $updateData = $request->only([
                'name',
                'date_of_birth',
            ]);

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $photoUrl = Helper::fileUpload($request->file('avatar'), 'users', $user->name . "-avatar-" . time());
                if ($photoUrl) {
                    $updateData['avatar'] = $photoUrl;

                    // Delete old avatar if it's not the default one
                    if ($user->avatar !== 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg') {
                        Helper::deleteFile($user->avatar);
                    }
                }
            }
            /**
             * @var User $user
             */
            $user->update($updateData);
            DB::commit();

            return $this->success([
                'user' => $user->fresh(),
            ], 'Profile updated successfully');

        }
        catch (Exception $e) {
            DB::rollBack();
            Log::error('Profile update failed: ' . $e->getMessage());
            return $this->error([], 'Failed to update profile: ' . $e->getMessage(), 500);
        }
    }


    public function changePassword(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'current_password' => 'required|string|max:255',
            'password' => 'required|string|max:255|confirmed',
        ]);

        if ($validation->fails()) {
            return $this->error([], $validation->errors(), 500);
        }

        try {
            $user = User::where('id', Auth::id())->first();
            if (password_verify($request->current_password, $user->password)) {
                $user->password = Hash::make($request->password_confirmation);
                $user->save();
                return $this->success([], "Password changed successfully", 200);
            }
            else {
                return $this->error([], "Old password is incorrect", 500);
            }
        }
        catch (Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }




    public function getMyNotifications()
    {
        $user = Auth::user();
        /**
         * @var User $user
         */
        $notifications = $user->notifications()->latest()->get();
        return $this->success([
            'notifications' => $notifications,
        ], "Notifications fetched successfully", 200);
    }




    public function deleteUser()
    {
        $user = User::where('id', Auth::id())->first();
        if ($user) {
            $user->delete();
            return $this->success([], "User deleted successfully", 200);
        }
        else {
            return $this->error("User not found", 404);
        }

    }

    public function updateAvatar(AvatarUpdateRequest $request)
    {
        try {
            $user = Auth::user();
            if ($request->hasFile('avatar')) {
                $photoUrl = Helper::fileUpload($request->file('avatar'), 'users/avatars', $user->name . "-avatar-" . time());
                if ($photoUrl) {
                    /**
                     * @var User $user
                     */
                    $oldAvatar = $user->getRawOriginal('avatar');
                    $user->avatar = $photoUrl;
                    $user->save();

                    // Delete old avatar if it's not the default one
                    if ($oldAvatar && $oldAvatar !== 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg' && file_exists(public_path($oldAvatar))) {
                        Helper::deleteFile($oldAvatar);
                    }
                }
            }

            return $this->success([
                'user' => new UserResource($user),
            ], 'Avatar updated successfully');
        } catch (Exception $e) {
            Log::error('Avatar update failed: ' . $e->getMessage());
            return $this->error([], 'Failed to update avatar: ' . $e->getMessage(), 500);
        }
    }

    public function updateCoverPhoto(CoverPhotoUpdateRequest $request)
    {
        try {
            $user = Auth::user();
            if ($request->hasFile('cover_photo')) {
                $photoUrl = Helper::fileUpload($request->file('cover_photo'), 'users/covers', $user->name . "-cover-" . time());
                if ($photoUrl) {
                    /**
                     * @var User $user
                     */
                    $oldCover = $user->getRawOriginal('cover_photo');
                    $user->cover_photo = $photoUrl;
                    $user->save();

                    if ($oldCover && file_exists(public_path($oldCover))) {
                        Helper::deleteFile($oldCover);
                    }

                    // If user is a vendor and has a business record, keep it in sync!
                    if ($user->user_type === 'vendor' && $user->business) {
                        $business = $user->business;
                        $oldBusinessCover = $business->cover_photo;
                        $business->cover_photo = $photoUrl;
                        $business->save();

                        if ($oldBusinessCover && file_exists(public_path($oldBusinessCover)) && $oldBusinessCover !== $oldCover) {
                            Helper::deleteFile($oldBusinessCover);
                        }
                    }
                }
            }

            return $this->success([
                'user' => new UserResource($user),
            ], 'Cover photo updated successfully');
        } catch (Exception $e) {
            Log::error('Cover photo update failed: ' . $e->getMessage());
            return $this->error([], 'Failed to update cover photo: ' . $e->getMessage(), 500);
        }
    }
}
