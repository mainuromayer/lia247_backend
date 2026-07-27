<?php

namespace App\Http\Controllers\Web\backend\settings;

use Exception;
use App\Models\User;
use App\Helper\Helper;
use App\Services\Service;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileSettingController extends Controller
{

    public function index(Request $request)
    {

        return view('backend.layout.setting.profileSettings');
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . Auth::id(),
        ]);


        if ($validator->fails()) {
            return redirect()->back()->with([
                'error' => $validator->errors()->first(),
                'type' => 'profile'
            ]);
        }

        try {
            $data = $request->all();

            User::find(Auth::id())->update($data);

            flash()->success('Profile updated successfully.');

            return redirect()->back();
        } catch (Exception $e) {
            flash()->error($e->getMessage());
            return redirect()->back();
        }
    }

    public function updatePassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'password' => 'required|string|confirmed',
        ]);


        if ($validator->fails()) {
            return redirect()->back()->with([
                'error' => $validator->errors()->first(),
                'type' => 'password'
            ]);
        }

        // dd($validatedData);

        try {

            if (!Hash::check($request->old_password, Auth::user()->password)) {
                throw new Exception('Old password does not match.');
            }

            $user = User::find(Auth::id());
            $user->password = Hash::make($request->password);
            $user->save();

            flash()->success('Password updated successfully.');
            return redirect()->route('profile');
        } catch (Exception $e) {
            flash()->error($e->getMessage());
            return redirect()->back();
        }
    }

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|string', // Validate as string (base64)
        ]);

        try {
            $user = User::find(Auth::id());


            // Decode base64 string
            $base64Image = $request->input('profile_picture');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif, etc.

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid image format. Allowed formats: jpeg, png, jpg, gif, svg.',
                    ], 422);
                }

                $imageData = base64_decode($base64Image);
                if ($imageData === false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid base64 data.',
                    ], 422);
                }

                // Check file size (20MB = 20 * 1024 * 1024 bytes)
                if (strlen($imageData) > 20 * 1024 * 1024) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The profile picture may not be greater than 20 megabytes.',
                    ], 422);
                }

                // Delete old avatar if it exists
                if ($user->avatar && $user->avatar != 'https://static.vecteezy.com/system/resources/previews/048/926/084/non_2x/silver-membership-icon-default-avatar-profile-icon-membership-icon-social-media-user-image-illustration-vector.jpg' && file_exists(public_path($user->avatar))) {
                    unlink(public_path($user->avatar));
                }

                // Save the image
                $filename = 'profile_picture' . uniqid() . '.' . $type;
                $path = 'uploads/profile_pictures/admins/' . $filename;
                file_put_contents(public_path($path), $imageData);

                $user->avatar = $path;
                $user->save();

                $imageUrl = asset($path);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile picture updated successfully.',
                    'image_url' => $imageUrl,
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid image data.',
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


}
